<?php
namespace backend\admin;

use backend\Base;
use backend\Configs;
use backend\Files;
use backend\Output;
use backend\Permission;
use stdClass;


abstract class HasWritePermission extends IsLoggedIn {
	//basic keys which are included in every questionnaire dataset
	const KEYS_QUESTIONNAIRE_BASE_RESPONSES = [
		'entryId',
		'userId',
		'uploaded',
		'appType',
		'appVersion',
		'studyId',
		'accessKey',
		'studyVersion',
		'studyLang',
		'questionnaireName',
		'eventType',
		'timezone',
		'responseTime',
		'responseTime_formatted', //will be created by the server
		'formDuration',
		'lastInvitation',
		'lastInvitation_formatted' //will be created by the server
	];

	//all event-related keys that are included in the event file:
	const KEYS_EVENT_RESPONSES = [
		'entryId',
		'userId',
		'uploaded',
		'appType',
		'appVersion',
		'studyId',
		'accessKey',
		'studyVersion',
		'studyLang',
		'questionnaireName',
		'eventType',
		'timezone',
		'responseTime',
		'responseTime_formatted', //will be created by the server
		'newSchedule',
		'actionScheduledTo',
		'actionScheduledTo_formatted', //will be created by the server
		'model',
		'osVersion',
		'manufacturer'
	];
	const KEYS_WEB_ACCESS = [
		'responseTime',
		'page',
		'referer',
		'user_agent',
	];
	
	
	
	protected function get_conditionString($key, $storageType, $timeInterval, $conditions) {
		$a = [];
		foreach($conditions as $c) {
			array_push($a, $c->key .$c->operator .$c->value);
		}
		sort($a);
		return $key .$storageType .$timeInterval .implode('', $a);
	}
	protected function write_statistics($study) {
		$study_id = $study->id;
		if($study->publicStatistics->observedVariables !== new stdClass()) { //check if empty
			$folder_statistics = Files::get_folder_statistics($study_id);
			$file_statisticsMetadata = Files::get_file_statisticsMetadata($study_id);
			$file_statisticsJson = Files::get_file_statisticsJson($study_id);
			
			if(!file_exists($folder_statistics))
				$this->create_folder($folder_statistics);
			
			$old_index = [];
			if(file_exists($file_statisticsJson)) {
				$old_statisticMetadata = unserialize(file_get_contents($file_statisticsMetadata));
				$old_statisticJson = json_decode(file_get_contents($file_statisticsJson));
				if(!empty($old_statisticJson)) {
					foreach($old_statisticJson as $value => $jsonKeyBox) {
						foreach($jsonKeyBox as $index => $jsonEntry) {
							$metadataEntry = $old_statisticMetadata->{$value}[$index];
							$old_index[$this->get_conditionString($value, $jsonEntry->storageType, $metadataEntry->defaultTimeInterval, $metadataEntry->conditions)] = $jsonEntry;
						}
					}
				}
			}
			
			$statistics_metadata = new stdClass();
			$statistics_json = new stdClass();
			
			foreach($study->publicStatistics->observedVariables as $value => $keyBox) {
				foreach($keyBox as $observedEntry) {
					if(!isset($statistics_metadata->{$value})) {
						$statistics_metadata->{$value} = [];
						$statistics_json->{$value} = [];
					}
					
					$metaDataObj = (object)['conditions' => $observedEntry->conditions, 'conditionType' => $observedEntry->conditionType, 'storageType' => $observedEntry->storageType, 'defaultTimeInterval' => $observedEntry->timeInterval];
					
					array_push($statistics_metadata->{$value}, $metaDataObj);
					
					$jsonObj = (object)['storageType' => $observedEntry->storageType, 'data' => new stdClass(), 'entryCount' => 0, 'timeInterval' => $observedEntry->timeInterval];
					
					
					$conditionString = $this->get_conditionString($value, $observedEntry->storageType, $observedEntry->timeInterval, $observedEntry->conditions);
					if(isset($old_index[$conditionString])) {
						$old_entry = $old_index[$conditionString];
						$jsonObj->data = $old_entry->data;
						$jsonObj->entryCount = $old_entry->entryCount;
						$jsonObj->timeInterval = $old_entry->timeInterval;
					}
					else {
						//TODO: extract statistics from already existing data
					}
					array_push($statistics_json->{$value}, $jsonObj);
				}
			}
			$this->write_file($file_statisticsMetadata, serialize($statistics_metadata));
			$this->write_file($file_statisticsJson, json_encode($statistics_json));
		}
	}
	protected function write_indexAndResponses_files($study, $identifier, $new_keys) {
		//Note: When there is already data:
		// If keys are removed, they will stay in the headers
		// if keys are changed or new, they will be added at the end

        if(isset($study->randomGroups) && $study->randomGroups >= 0 && $identifier !== Files::FILENAME_WEB_ACCESS)
            array_splice($new_keys['keys'], 1, 0, 'group');
//        $new_keys[] = 'group';
//        print_r($new_keys);

		$study_id = (int) $study->id;
		$file_responses = Files::get_file_responses($study_id, $identifier);
		$file_index = Files::get_file_responsesIndex($study_id, $identifier);
		
		$csv_delimiter = Configs::get('csv_delimiter');
		
		if(file_exists($file_responses) && file_exists($file_index)) {
			$old_keys = unserialize(file_get_contents($file_index));
			$old_keys['types'] = $new_keys['types'];
			
			//finding out if there are new headers:
			$index = [];
			foreach($new_keys['keys'] as $value) {
				$index[$value] = $value;
			}
			foreach($old_keys['keys'] as $value) {
				unset($index[$value]);
			}
			
			if(!empty($index)) {
				$file_responsesBackup = Files::get_file_responsesBackup($study_id, $identifier);
				
				Base::freeze_study($study_id);
				
				
				//move responses to a backup:
				if(rename($file_responses, $file_responsesBackup))
					chmod($file_responsesBackup, 0666);
				else {
					Base::freeze_study($study_id, false);
					Output::error("Could not rename $file_responses to $file_responsesBackup");
				}
				
				//if the file is too big to be changed on the fly, we just create a new file and that's it:
				if(filesize($file_responsesBackup) > Configs::get('max_filesize_for_changes')) {
					$this->write_file($file_responses, '"'.implode('"'.$csv_delimiter.'"', $new_keys['keys']).'"');
					$this->write_file($file_index, serialize($new_keys));
					Base::freeze_study($study_id, false);
					return;
				}
				
				
				//Preparing new header adding new keys to $old_key and preparing and $addedContent
				$addedContent = '';
				foreach($index as $value) {
					$addedContent .= $csv_delimiter .'""';
					$old_keys['keys'][] = $value;
				}
				
				
				
				//we read the backup and create a new responses file from that:
				
				$handle_newResponses = fopen($file_responses, 'w');
				$handle_backup = fopen($file_responsesBackup, 'r');
				
				if(!$handle_newResponses || !$handle_backup) {
					Base::freeze_study($study_id, false);
					Output::error("Could not open $file_responses or $file_responsesBackup");
				}
				flock($handle_newResponses, LOCK_EX);
				
				fgets($handle_backup); //loading first line - this is the old header. We dont need it
				
				
				if(feof($handle_backup)) { //there is no data. So we can just use the new headers
					fputs($handle_newResponses, '"'.implode('"'.$csv_delimiter.'"', $new_keys['keys']).'"');
					$this->write_file($file_index, serialize($new_keys));
					unlink($file_responsesBackup); //there is no point in keeping this backup
				}
				else {
					fputs($handle_newResponses, '"'.implode('"'.$csv_delimiter.'"', $old_keys['keys']).'"');
					
					while(($line = fgets($handle_backup)) !== false) {
						fputs($handle_newResponses, "\n".rtrim($line, "\n").$addedContent);
					}
					
					$this->write_file($file_index, serialize($old_keys));
				}
				
				
				fflush($handle_newResponses);
				flock($handle_newResponses, LOCK_UN);
				fclose($handle_newResponses);
				fclose($handle_backup);
				Base::freeze_study($study_id, false);
			}
		}
		else {
			$this->write_file($file_responses, '"'.implode('"'.$csv_delimiter.'"', $new_keys['keys']).'"');
			$this->write_file($file_index, serialize($new_keys));
		}
	}
	protected function checkUnique_and_collectKeys($study, $study_index) {
		//Note: When a questionnaire is deleted, its internalId will stay in the index until the study is unpublished or deleted.
		//The only solution I can think of would be to loop through the complete index every time a study is saved.
		//But since this case will rarely happen and probably wont ever be a problem and the loop can be an expensive operation, we just ignore this problem.
		
		$study_id = $study->id;
		$internalId_index = [];
		$key_check_array = [];
		$keys_questionnaire_array = [];
		foreach($study->questionnaires as $i => &$questionnaire) {
			//make sure internalIds are unique:
			if(
				!isset($questionnaire->internalId) ||
				$questionnaire->internalId === -1 ||
				isset($internalId_index[$questionnaire->internalId]) ||
				(isset($study_index['~'.$questionnaire->internalId]) && $study_index['~'.$questionnaire->internalId][0] != $study_id)
			) {
				do {
					$internalId = $this->getQuestionnaireId();
				} while(isset($internalId_index[$internalId]) || isset($study_index['~'.$internalId]));
				$old_internalId = $questionnaire->internalId;
				$questionnaire->internalId = $internalId;
				$internalId_index[$internalId] = true;
				
				foreach($study->questionnaires as $q) {
					foreach($q->actionTriggers as $actionTrigger) {
						foreach($actionTrigger->eventTriggers as $eventTrigger) {
							if(isset($eventTrigger->specificQuestionnaireInternalId) && $eventTrigger->specificQuestionnaireInternalId == $old_internalId)
								$eventTrigger->specificQuestionnaireInternalId = $internalId;
						}
					}
				}
			}
			else
				$internalId_index[$questionnaire->internalId] = true;
			
			//check questionnaire:
			if(!isset($questionnaire->title) || !strlen($questionnaire->title))
				Output::error('Questionnaire title is empty!');
			
			$questionnaire_title = $questionnaire->title; //only used for error feedback
			$keys_questionnaire = self::KEYS_QUESTIONNAIRE_BASE_RESPONSES; //Note: php always creates copies, which is what we need right now
			$input_types = [];
			
			//make sure input and sumScore names are unique:
			if(isset($questionnaire->pages)) {
				foreach($questionnaire->pages as $page) {
					foreach($page->inputs as $input) {
						$responseType = isset($input->responseType) ? $input->responseType : 'text_input';
						
						$name = $input->name;
						
						if(!strlen($name))
							Output::error('Input name is empty!');
						else if(!Base::check_input($name))
							Output::error("No special characters are allowed in Variable-Names. \n'$name' detected in questionnaire: $questionnaire_title");
						else if(isset($key_check_array[$name]))
							Output::error("Variable-Name exists more than once: '$name'. First detected in questionnaire: '".$key_check_array[$input->name]."'. Detected again in questionnaire: '$questionnaire_title'");
						else if(in_array($name, self::KEYS_EVENT_RESPONSES) || in_array($name, self::KEYS_QUESTIONNAIRE_BASE_RESPONSES))
							Output::error("Protected Variable-Name: $name \nPlease choose another Variable-Name.\nDetected in questionnaire: $questionnaire_title");
						else
							$key_check_array[$name] = $questionnaire_title;
						
						switch($responseType) {
							case 'text':
								unset($key_check_array[$name]);
								continue 2;
							case 'dynamic_input':
								$keys_questionnaire[] = $name;
								$keys_questionnaire[] = "$name~index";
								break;
							case 'app_usage':
								$keys_questionnaire[] = $name;
								$keys_questionnaire[] = "$name~usageTimeFromApps"; //TODO - for testing
								$keys_questionnaire[] = "$name~usageCount";
								break;
							case 'photo':
								$keys_questionnaire[] = $name;
								$input_types[$name] = 'image';
								break;
							default:
								$keys_questionnaire[] = $name;
								break;
						}
					}
				}
			}
			if(isset($questionnaire->sumScores)) {
				foreach($questionnaire->sumScores as $score) {
					if(!isset($score->name))
						continue;
					$keys_questionnaire[] = $score->name;
				}
			}

			$keys_questionnaire_array[$i] = ['keys' => $keys_questionnaire, 'types' => $input_types];
		}
		return $keys_questionnaire_array;
	}
	
	function __construct() {
		parent::__construct();
		if($this->study_id == 0)
			Output::error('Missing study id');
		if(!$this->is_admin && !Permission::has_permission($this->study_id, 'write'))
			Output::error('No permission');
	}
}

?>