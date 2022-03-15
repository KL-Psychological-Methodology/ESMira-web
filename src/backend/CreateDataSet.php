<?php

namespace backend;

use Exception;
use backend\Base;
use backend\Files;
use stdClass;

const ONE_DAY = 86400; //in seconds: 60*60*24

class CreateDataSet {
	const DATASET_TYPE_JOINED = 'joined';
	const DATASET_TYPE_QUIT = 'quit';
	const DATASET_TYPE_QUESTIONNAIRE = 'questionnaire';
	
	//TODO: change into Strings:
	const CONDITION_TYPE_ALL = 0,
		CONDITION_TYPE_AND = 1,
		CONDITION_TYPE_OR = 2,
		
		CONDITION_OPERATOR_EQUAL = 0,
		CONDITION_OPERATOR_UNEQUAL = 1,
		CONDITION_OPERATOR_GREATER = 2,
		CONDITION_OPERATOR_LESS = 3,
		
		STATISTICS_STORAGE_TYPE_TIMED = 0,
		STATISTICS_STORAGE_TYPE_FREQ_DISTR = 1,
		
		STATISTICS_CHARTTYPES_LINE = 0,
		STATISTICS_CHARTTYPES_LINE_FILLED = 1,
		STATISTICS_CHARTTYPES_BARS = 2,
		STATISTICS_CHARTTYPES_PIE = 3,
		
		STATISTICS_DATATYPES_DAILY = 0,
		STATISTICS_DATATYPES_FREQ_DISTR = 1,
		STATISTICS_DATATYPES_SUM = 2,
		STATISTICS_DATATYPES_XY = 3;
	
	
	
	
	public $output = [];
	public $new_studyTokens;
	
	private $output_index = [];
	private $write_cache = [];
	private $file_cache = [];
	private $questionnaire_events = 0;
	private $join_events = 0;
	private $total_users = 0; //will only be 0 or 1

	private $dataColumns_index = [];
	
	private $app_type, $app_version, $user_id;
	
	static function format_date($time) {
		return date('Y/m/d H:i:s', round($time / 1000));
	}
	static function get_first_obj_key($obj) {
		foreach($obj as $key => $value) {
			return $key;
		}
		return null;
	}
	static function addEvent_to_statistics(&$statistics, &$current, $event, $count) {
		if(!isset($current->{$event}))
			$current->{$event} = $count;
		else
			$current->{$event} += $count;
		
		$statistics->week->{$event}[date('w')] += $count;
		$statistics->total->{$event} += $count;
	}
	
	function success_lineOutput($dataset_id) {
		if(!isset($this->output_index[$dataset_id])) {
			$this->output_index[$dataset_id] = count($this->output);
			$this->output[] = ['dataSetId' => $dataset_id, 'success' => true];
		}
	}
	function error_lineOutput($dataset_id, $s) {
		if(isset($this->output_index[$dataset_id]))
			$this->output[$dataset_id] = ['dataSetId' => $dataset_id, 'success' => false, 'error' => $s];
		else {
			$this->output_index[$dataset_id] = count($this->output);
			$this->output[] = ['dataSetId' => $dataset_id, 'success' => false, 'error' => $s];
		}
	}
	
	function getIndex($study_id, $identifier) {
		if(isset($this->dataColumns_index[$study_id.$identifier]))
			return $this->dataColumns_index[$study_id.$identifier];
		else {
			$path = Files::get_file_responsesIndex($study_id, $identifier);
			if(file_exists($path)) {
				$index = unserialize(file_get_contents($path));
				if($index == null)
					return null;
				
				if(!isset($index['keys'])) //TODO: check is needed for old index design
					$index = ['keys' => $index, 'types' => []];
				
				return $this->dataColumns_index[$study_id . $identifier] = $index;
			}
			else {
				Base::report("$path does not exist! Canceled saving for study $study_id");
				return null;
			}
		}
	}
	
	function addTo_fileCache($study_id, $file_url, $identifier, $dataset_id) {
		$this->file_cache[$dataset_id] = ['study_id' => $study_id, 'path' => $file_url, 'identifier' => $identifier];
	}
	function addTo_writeCache($filename, $content, $dataset_id) {
		if(isset($this->write_cache[$filename])) {
			$this->write_cache[$filename]['cache'] .= $content;
			array_push($this->write_cache[$filename]['ids'], $dataset_id);
		}
		else {
			$this->write_cache[$filename] = [
				'ids' => [$dataset_id],
				'cache' => $content
			];
		}
	}
	
	/**
	 * @throws Exception
	 */
	function __construct($json) {
		$this->new_studyTokens = new stdClass();
		$this->prepare($json);
		$this->exec();
	}
	
	/**
	 * @throws Exception
	 */
	function prepare($json) {
		if(!isset($json->userId) || !isset($json->appVersion) || !isset($json->appType) || !isset($json->dataset) || !isset($json->serverVersion)) {
			throw new Exception('Unexpected data');
		}
		
		if($json->serverVersion < Base::ACCEPTED_SERVER_VERSION)
			throw new Exception('This app is outdated. Aborting');
		
		$uploaded = Base::get_milliseconds();
		$this->user_id = $json->userId;
		$this->app_type = $json->appType;
		$this->app_version = $json->appVersion;
		
		if(!Base::check_input($this->user_id) || !Base::check_input($this->app_type) || !Base::check_input($this->app_version))
			throw new Exception('Input data not valid');
		
		$metadata_index = [];
		$statisticMetadata_index = [];
		$current_studyTokens = [];
		$csv_delimiter = Configs::get('csv_delimiter');
		
		foreach($json->dataset as $dataSet) {
			$responses = $dataSet->responses;
			
			if(isset($responses->actionScheduledTo))
				$responses->actionScheduledTo_formatted = self::format_date($responses->actionScheduledTo);
			if(isset($responses->lastInvitation) && $responses->lastInvitation != 0)
				$responses->lastInvitation_formatted = self::format_date($responses->lastInvitation);
			
			$responses->userId = $this->user_id;
			$responses->uploaded = $uploaded;
			$responses->appType = $this->app_type;
			$responses->appVersion = $this->app_version;
			
			$dataSet->studyVersion = ((int) $dataSet->studyVersion).'.'.((int) $dataSet->studySubVersion);
			
			$study_id = (int) $dataSet->studyId;
			$dataset_id = (int) $dataSet->dataSetId;
			$event_type = $dataSet->eventType;
			
			if(Base::study_is_locked($study_id)) {
				$this->error_lineOutput($dataset_id, 'Study is locked');
				continue;
			}
			
			//*****
			//check accessKey:
			//*****
			
			if(!isset($metadata_index[$study_id])) {
				$metadata_path = Files::get_file_studyMetadata($study_id);
				if(!file_exists($metadata_path)) {
					$this->error_lineOutput($dataset_id, 'Study does not exist');
					continue;
				}
				$metadata = unserialize(file_get_contents($metadata_path));
				$metadata_index[$study_id] = $metadata;
			}
			else
				$metadata = $metadata_index[$study_id];
			if(isset($metadata['accessKeys']) && sizeof($metadata['accessKeys']) && (!isset($dataSet->accessKey) || !in_array(strtolower($dataSet->accessKey), $metadata['accessKeys']))) {
				$this->error_lineOutput($dataset_id, 'Wrong accessKey');
				continue;
			}
			
			//*****
			//check token:
			//*****
			
			//get current token:
			if(isset($current_studyTokens[$study_id]))
				$currentToken = $current_studyTokens[$study_id];
			else {
				$file_token = Files::get_file_userData($study_id, $this->user_id);
				if(file_exists($file_token)) {
					$userdata = unserialize(file_get_contents($file_token));
					$currentToken = $userdata['token'];
				}
				else {
					$currentToken = -1;
					++$this->total_users;
				}
				$current_studyTokens[$study_id] = $currentToken;
			}
			
			
			//get / create new token:
			if(isset($this->new_studyTokens->{$study_id}))
				$newToken = $this->new_studyTokens->{$study_id};
			else {
				if(!isset($file_token))
					$file_token = Files::get_file_userData($study_id, $this->user_id);
				$newToken = Base::get_milliseconds();
				$userdata = [
					'token' => $newToken,
					'appVersion' => $this->app_version,
					'appType' => $this->app_type
				];
				if(!file_put_contents($file_token, serialize($userdata), LOCK_EX)) {
					Base::report("Could not save token for user \"$this->user_id\" in study $study_id");
					$newToken = -1;
				}
				$this->new_studyTokens->{$study_id} = $newToken;
			}
			
			//check for too many requests:
			if($currentToken !== -1 && $newToken !== -1 && $newToken - $currentToken < Configs::get('dataset_server_timeout')) {
				$this->error_lineOutput($dataset_id, "Too many requests in succession");
				continue;
			}
			
			//check if data was reuploaded with outdated token:
			if(isset($dataSet->token)) {
				$sentToken = (int) $dataSet->token;
				if($sentToken != 0 && $newToken !== -1 && isset($dataSet->reupload) && $dataSet->reupload && $sentToken != $currentToken) {
					$this->success_lineOutput($dataset_id); //data was already sent
					continue;
				}
			}
			
			
			$dataSet_questionnaireName = isset($dataSet->questionnaireName) ? $dataSet->questionnaireName : '';
			
			
			
			if((!Base::check_input($dataSet_questionnaireName)) || !Base::check_input($event_type)) {
				$this->error_lineOutput($dataset_id, "Unexpected input! Group: $dataSet_questionnaireName; Event-Type: $event_type");
				continue;
			}
			else if(!file_exists(Files::get_folder_study($study_id))) {
				$this->error_lineOutput($dataset_id, "Study $study_id does not exist");
				continue;
			}
			
			$eventIndex = $this->getIndex($study_id, Files::FILENAME_EVENTS);
			if($eventIndex == null) {
				$this->error_lineOutput($dataset_id, "Study $study_id seems to be broken");
				continue;
			}
			
			
			//format responseTime:
			if(isset($dataSet->responseTime))
				$dataSet->responseTime_formatted = self::format_date($dataSet->responseTime);
			
			
			
			
			//*****
			//create base output:
			//*****
			
			if($event_type === self::DATASET_TYPE_QUESTIONNAIRE) {
				$dataSet_questionnaireId = isset($dataSet->questionnaireInternalId) ? $dataSet->questionnaireInternalId : -1;
				$file_questionnaire = Files::get_file_responses($study_id, $dataSet_questionnaireId);
				
				if(!file_exists($file_questionnaire)) {
					$this->error_lineOutput($dataset_id, "Group '$dataSet_questionnaireName' (id=$dataSet_questionnaireId) does not exist");
					continue;
				}
				
				
				$file_statistics_newData = Files::get_file_statisticsNewData($study_id);
				
				if(isset($statisticMetadata_index[$study_id]))
					$statistics_metadata = $statisticMetadata_index[$study_id];
				else {
					$file_statistics_metadata = Files::get_file_statisticsMetadata($study_id);
					$statistics_metadata = file_exists($file_statistics_metadata) ? unserialize(file_get_contents($file_statistics_metadata)) : new stdClass();
					$statisticMetadata_index[$study_id] = $statistics_metadata;
				}
				
				
				//*****
				//fill questionnaire output:
				//*****
				$questionnaire_index = $this->getIndex($study_id, $dataSet_questionnaireId);
				
				if($questionnaire_index == null) {
					$this->error_lineOutput($dataset_id, "Study $study_id seems to be broken");
					continue;
				}
				
				$types = $questionnaire_index['types'];
				$questionnaire_write = [];
				$statistic_write = '';
				
				
				foreach($questionnaire_index['keys'] as $key) {
					if(isset($responses->{$key}))
						$answer = Base::strip_oneLineInput($responses->{$key});
					else if(isset($dataSet->{$key}))
						$answer = Base::strip_oneLineInput($dataSet->{$key});
					else
						$answer = '';
					
					
					if(isset($types[$key]) && $types[$key] === 'image') { // we are expecting a file:
						$file_urlData = Files::get_fileAndName_image($study_id, $this->user_id, $dataset_id, $dataSet->responseTime, $key);
						$this->addTo_fileCache($study_id, $file_urlData[1], $answer, $dataset_id);
						$answer = "images/$this->user_id/$file_urlData[0]";
					}
					
					$questionnaire_write[] = $answer;
					
					
					//statistics:
					if(isset($statistics_metadata->{$key})) {
						$current_statistic = &$statistics_metadata->{$key};
						
						foreach($current_statistic as $i => $conditional_statistics) {
							$condition_is_met = true;
							if($conditional_statistics->conditionType != self::CONDITION_TYPE_ALL) {
								$conditionType_isOr = $conditional_statistics->conditionType == self::CONDITION_TYPE_OR;
								$conditionType_isAnd = $conditional_statistics->conditionType == self::CONDITION_TYPE_AND;
								
								foreach($conditional_statistics->conditions as $condition) {
									switch($condition->operator) {
										case self::CONDITION_OPERATOR_EQUAL:
											$is_true = $responses->{$condition->key} == $condition->value;
											break;
										case self::CONDITION_OPERATOR_UNEQUAL:
											$is_true = $responses->{$condition->key} != $condition->value;
											break;
										case self::CONDITION_OPERATOR_GREATER:
											$is_true = $responses->{$condition->key} >= $condition->value;
											break;
										case self::CONDITION_OPERATOR_LESS:
											$is_true = $responses->{$condition->key} <= $condition->value;
											break;
										default:
											$is_true = true;
									}
									
									if($is_true) {
										if($conditionType_isOr) {
											$condition_is_met = true;
											break;
										}
									}
									else if($conditionType_isAnd) {
										$condition_is_met = false;
										break;
									}
								}
							}
							
							
							if($condition_is_met)
								$statistic_write .= "$key|$i|".$dataSet->responseTime.'|'.str_replace('|', '_', $answer)."\n";
						}
					}
				}
				
				//*****
				//Export data
				//*****
				
				++$this->questionnaire_events;
				
				//statistics:
				if(!empty($statistic_write))
					$this->addTo_writeCache($file_statistics_newData, $statistic_write, $dataset_id);
				
				//questionnaire:
				$this->addTo_writeCache($file_questionnaire, "\n\"".implode('"'.$csv_delimiter.'"', $questionnaire_write).'"', $dataset_id);
			}
			else if($event_type === self::DATASET_TYPE_JOINED) {
				++$this->join_events;
			}
			
			
			//*****
			//fill event output:
			//*****
			
			$events_write = [];
			foreach($eventIndex['keys'] as $key) {
				if(isset($dataSet->{$key}))
					$events_write[] = Base::strip_oneLineInput($dataSet->{$key});
				else if(isset($responses->{$key}))
					$events_write[] = Base::strip_oneLineInput($responses->{$key});
				else
					$events_write[] = '';
			}
			
			
			//*****
			//write event data:
			//*****
			$this->addTo_writeCache(
				Files::get_file_responses($study_id, Files::FILENAME_EVENTS),
				"\n\"".implode('"'.$csv_delimiter.'"', $events_write).'"',
				$dataset_id
			);
		}
	}
	
	function add_fileReceiver($dataset_id, $study_id, $identifier, $path) {
		$folder = Files::get_file_pendingUploads($study_id, $this->user_id, $identifier);
		if(!file_put_contents($folder, $path, LOCK_EX))
			$this->error_lineOutput($dataset_id, 'Internal Server Error: Saving failed');
	}
	
	function exec() {
		foreach($this->file_cache as $dataset_id => $entry) {
			self::add_fileReceiver($dataset_id, $entry['study_id'], $entry['identifier'], $entry['path']);
		}
		
		foreach($this->write_cache as $file => $data) {
			if(file_put_contents($file, $data['cache'], FILE_APPEND | LOCK_EX)) {
				foreach($data['ids'] as $dataset_id) {
					$this->success_lineOutput($dataset_id);
				}
			}
			else {
				Base::report("Could not write to file '$file'. Sending error response to app.");
				foreach($data['ids'] as $dataset_id) {
					$this->error_lineOutput($dataset_id, 'Internal Server Error: Saving failed');
				}
			}
		}
		
		Base::update_serverStatistics(function(&$statistics, $values) {
			list($app_type, $app_version, $total_users, $questionnaire_events, $join_events) = $values;
			
			$is_dev = false;
			if($total_users !== 0) {
				$statistics->total->users += $total_users;
				switch($app_type) {
					case 'Android':
					case 'Android_wasDev':
						$statistics->total->android += $total_users;
						break;
					case 'iOS':
					case 'iOS_wasDev':
						$statistics->total->ios += $total_users;
						break;
					case 'Web':
					case 'Web-NOJS':
						$statistics->total->web += $total_users;
						break;
					default:
						$is_dev = true;
				}
			}
			
			if(!$is_dev) {
				$start_of_day = floor(time() / ONE_DAY) * ONE_DAY;
				$oldest_entry_time = $start_of_day - ONE_DAY * Configs::get('number_of_saved_days_in_server_statistics');
				$box = $statistics->days;
				
				if(!isset($box->{$start_of_day})) {
					$box->{$start_of_day} = new stdClass();
					
					while(($key = self::get_first_obj_key($box)) < $oldest_entry_time && $key != null) {
						unset($box->{$key});
					}
				}
				
				$versionString = $app_type .' ' .$app_version;
				$current = &$box->{$start_of_day};
				if(!isset($current->appVersion))
					$current->appVersion = [$versionString => 1];
				else if(!isset($current->appVersion->{$versionString}))
					$current->appVersion->{$versionString} = 1;
				else
					$current->appVersion->{$versionString} += 1;
				
				
				if($questionnaire_events != 0)
					self::addEvent_to_statistics($statistics, $current, 'questionnaire', $questionnaire_events);
				
				if($join_events != 0)
					self::addEvent_to_statistics($statistics, $current, 'joined', $join_events);
				
				return false;
			}
			else
				return true;
		}, [$this->app_type, $this->app_version, $this->total_users, $this->questionnaire_events, $this->join_events]);
	}
}