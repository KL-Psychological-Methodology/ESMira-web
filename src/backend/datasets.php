<?php
require_once 'php/configs.php';
require_once 'php/files.php';
require_once 'php/string_fu.php';

ignore_user_abort(true);
set_time_limit(0);


$output_index = [];

function success_lineOutput(&$output, $dataset_id, &$output_index) {
	if(!isset($output_index[$dataset_id])) {
		$output_index[$dataset_id] = count($output);
		$output[] = ['dataSetId' => $dataset_id, 'success' => true];
	}
}
function error_lineOutput(&$output, $dataset_id, $s, &$output_index) {
	if(isset($output_index[$dataset_id]))
		$output[$dataset_id] = ['dataSetId' => $dataset_id, 'success' => false, 'error' => $s];
	else {
		$output_index[$dataset_id] = count($output);
		$output[] = ['dataSetId' => $dataset_id, 'success' => false, 'error' => $s];
	}
}

function format_date($time) {
	return date('Y/m/d H:i:s', round($time / 1000));
}

function getIndex(&$index, $study_id, $identifier) {
	if(isset($index[$study_id.$identifier]))
		return $index[$study_id.$identifier];
	else {
		$path = get_file_responsesIndex($study_id, $identifier);
		if(file_exists($path))
			return $index[$study_id.$identifier] = unserialize(file_get_contents($path));
		else {
			report("$path does not exist! Canceled saving for study $study_id");
			return null;
		}
	}
}

function addTo_writeCache(&$write_cache, $filename, $content, $dataset_id) {
	if(isset($write_cache[$filename])) {
		$write_cache[$filename]['cache'] .= $content;
		array_push($write_cache[$filename]['ids'], $dataset_id);
	}
	else {
		$write_cache[$filename] = [
			'ids' => [$dataset_id],
			'cache' => $content
		];
	}
}


if($_SERVER['REQUEST_METHOD'] === 'POST') {
	if(isset($html)) {
		require_once 'php/global_html.php';
	}
	else {
		header('Content-Type: application/json');
		header('Cache-Control: no-cache, must-revalidate');
		require_once 'php/global_json.php';
		
		$rest_json = file_get_contents('php://input');
		if(!($json = json_decode($rest_json)))
			return error('Unexpected data format');
	}
}
else {
	require_once 'php/global_json.php';
	return error('No Data');
}

$output = [];
$write_cache = [];
$questionnaire_events = 0;
$join_events = 0;
$total_users = 0; //will only be 0 or 1

try {
	if(!isset($json->userId) || !isset($json->appVersion) || !isset($json->appType) || !isset($json->dataset) || !isset($json->serverVersion)) {
		return error('Unexpected data');
	}
	
	if($json->serverVersion < ACCEPTED_SERVER_VERSION)
		error('This app is outdated. Aborting');
	
	$dataSets = $json->dataset;
	
	
	$user_id = $json->userId;
	$uploaded = get_milliseconds();
	$app_type = $json->appType;
	$app_version = $json->appVersion;
	
	if(!check_input($user_id) || !check_input($app_type) || !check_input($app_version))
		return error('Input data not valid');
	
	$dataColumns_index = [];
	$metadata_index = [];
	$statisticMetadata_index = [];
	$current_studyTokens = [];
	$new_studyTokens = new stdClass();
	
	foreach($dataSets as $dataSet) {
		$responses = $dataSet->responses;
		
		//format times: //TODO: do locally!
		if(isset($responses->actionScheduledTo))
			$responses->actionScheduledTo_formatted = format_date($responses->actionScheduledTo);
		if(isset($responses->lastInvitation) && $responses->lastInvitation != 0)
			$responses->lastInvitation_formatted = format_date($responses->lastInvitation);
		
		$responses->userId = $user_id;
		$responses->uploaded = $uploaded;
		$responses->appType = $app_type;
		$responses->appVersion = $app_version;
		
		$dataSet->studyVersion = ((int) $dataSet->studyVersion).'.'.((int) $dataSet->studySubVersion);
		
		$study_id = (int) $dataSet->studyId;
		$dataset_id = (int) $dataSet->dataSetId;
		$event_type = $dataSet->eventType;
		
		if(study_is_locked($study_id)) {
			error_lineOutput($output, $dataset_id, 'Study is locked', $output_index);
			continue;
		}
		
		//*****
		//check accessKey:
		//*****
		
		if(!isset($metadata_index[$study_id])) {
			$metadata_path = get_file_studyMetadata($study_id);
			if(!file_exists($metadata_path)) {
				error_lineOutput($output, $dataset_id, 'Study does not exist', $output_index);
				continue;
			}
			$metadata = unserialize(file_get_contents($metadata_path));
			$metadata_index[$study_id] = $metadata;
		}
		else
			$metadata = $metadata_index[$study_id];
		if(isset($metadata['accessKeys']) && sizeof($metadata['accessKeys']) && (!isset($dataSet->accessKey) || !in_array(strtolower($dataSet->accessKey), $metadata['accessKeys']))) {
			error_lineOutput($output, $dataset_id, 'Wrong accessKey', $output_index);
			continue;
		}
		
		//*****
		//check token:
		//*****
		
		//get current token:
		if(isset($current_studyTokens[$study_id]))
			$currentToken = $current_studyTokens[$study_id];
		else {
			$file_token = get_file_userData($study_id, $user_id);
			if(file_exists($file_token)) {
				$userdata = unserialize(file_get_contents($file_token));
				$currentToken = $userdata['token'];
			}
			else {
				$currentToken = -1;
				++$total_users;
			}
			$current_studyTokens[$study_id] = $currentToken;
		}
		
		
		//get / create new token:
		if(isset($new_studyTokens->{$study_id}))
			$newToken = $new_studyTokens->{$study_id};
		else {
			if(!isset($file_token))
				$file_token = get_file_userData($study_id, $user_id);
			$newToken = get_milliseconds();
			$userdata = [
				'token' => $newToken,
				'appVersion' => $app_version,
				'appType' => $app_type
			];
			if(!file_put_contents($file_token, serialize($userdata), LOCK_EX)) {
				report("Could not save token for user \"$user_id\" in study $study_id");
				$newToken = -1;
			}
			$new_studyTokens->{$study_id} = $newToken;
		}
		
		//check for too many requests:
		if($currentToken !== -1 && $newToken !== -1 && $newToken - $currentToken < DATASET_SERVER_TIMEOUT) {
			error_lineOutput($output, $dataset_id, "Too many requests in succession", $output_index);
			continue;
		}
		
		//check if data was reuploaded with outdated token:
		if(isset($dataSet->token)) {
			$sentToken = (int) $dataSet->token;
			if($sentToken != 0 && $newToken !== -1 && isset($dataSet->reupload) && $dataSet->reupload && $sentToken != $currentToken) {
				success_lineOutput($output, $dataset_id, $output_index); //data was already sent
				continue;
			}
		}
		
		
		$dataSet_questionnaireName = isset($dataSet->questionnaireName) ? $dataSet->questionnaireName : '';
		
		
		
		if((!check_input($dataSet_questionnaireName)) || !check_input($event_type)) {
			error_lineOutput($output, $dataset_id, "Unexpected input! Group: $dataSet_questionnaireName; Event-Type: $event_type", $output_index);
			continue;
		}
		else if(!file_exists(get_folder_study($study_id))) {
			error_lineOutput($output, $dataset_id, "Study $study_id does not exist", $output_index);
			continue;
		}
		
		$eventIndex = getIndex($dataColumns_index, $study_id, FILENAME_EVENTS);
		if($eventIndex == null) {
			error_lineOutput($output, $dataset_id, "Study $study_id seems to be broken", $output_index);
			continue;
		}
		
		
		//format responseTime:
		if(isset($dataSet->responseTime))
			$dataSet->responseTime_formatted = format_date($dataSet->responseTime);
		
		
		
		
		//*****
		//create base output:
		//*****
		
		$errorMsg = null;
		if($event_type === DATASET_TYPE_QUESTIONNAIRE) {
			$dataSet_questionnaireId = isset($dataSet->questionnaireInternalId) ? $dataSet->questionnaireInternalId : -1;
			$file_questionnaire = get_file_responses($study_id, $dataSet_questionnaireId);
			
			if(!file_exists($file_questionnaire)) {
				error_lineOutput($output, $dataset_id, "Group '$dataSet_questionnaireName' (id=$dataSet_questionnaireId) does not exist", $output_index);
				continue;
			}
			
			
			$file_statistics_newData = get_file_statisticsNewData($study_id);
			
			if(isset($statisticMetadata_index[$study_id]))
				$statistics_metadata = $statisticMetadata_index[$study_id];
			else {
				$file_statistics_metadata = get_file_statisticsMetadata($study_id);
				$statistics_metadata = file_exists($file_statistics_metadata) ? unserialize(file_get_contents($file_statistics_metadata)) : new stdClass();
				$statisticMetadata_index[$study_id] = $statistics_metadata;
			}
			
			
			//*****
			//fill questionnaire output:
			//*****
			$questionnaire_index = getIndex($dataColumns_index, $study_id, $dataSet_questionnaireId);
			
			if($questionnaire_index == null) {
				error_lineOutput($output, $dataset_id, "Study $study_id seems to be broken", $output_index);
				continue;
			}
			
			$questionnaire_write = [];
			$statistic_write = '';
			
			foreach($questionnaire_index as $key) {
				if(isset($responses->{$key}))
					$answer = strip_oneLineInput($responses->{$key});
				else if(isset($dataSet->{$key}))
					$answer = strip_oneLineInput($dataSet->{$key});
				else
					$answer = '';
				
				$questionnaire_write[] = $answer;
				
				
				//statistics:
				if(isset($statistics_metadata->{$key})) {
					$current_statistic = &$statistics_metadata->{$key};
					
					foreach($current_statistic as $i => $conditional_statistics) {
						$condition_is_met = true;
						if($conditional_statistics->conditionType != CONDITION_TYPE_ALL) {
							$conditionType_isOr = $conditional_statistics->conditionType == CONDITION_TYPE_OR;
							$conditionType_isAnd = $conditional_statistics->conditionType == CONDITION_TYPE_AND;
							
							foreach($conditional_statistics->conditions as $condition) {
								switch($condition->operator) {
									case CONDITION_OPERATOR_EQUAL:
										$is_true = $responses->{$condition->key} == $condition->value;
										break;
									case CONDITION_OPERATOR_UNEQUAL:
										$is_true = $responses->{$condition->key} != $condition->value;
										break;
									case CONDITION_OPERATOR_GREATER:
										$is_true = $responses->{$condition->key} >= $condition->value;
										break;
									case CONDITION_OPERATOR_LESS:
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
			
			++$questionnaire_events;
			
			//statistics:
			if(!empty($statistic_write))
				addTo_writeCache($write_cache, $file_statistics_newData, $statistic_write, $dataset_id);
			
			//questionnaire:
			addTo_writeCache($write_cache, $file_questionnaire, "\n\"".implode('"'.CSV_DELIMITER.'"', $questionnaire_write).'"', $dataset_id);
		}
		else if($event_type === DATASET_TYPE_JOINED) {
			++$join_events;
		}
		
		
		//*****
		//fill event output:
		//*****
//		$ip = $_SERVER['REMOTE_ADDR'];
//		if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
//			$responses->ip = preg_replace('/(?::[\da-f]*){4}$/', ':XXXX:XXXX:XXXX:XXXX', $ip);
//		else
//			$responses->ip = preg_replace('/\.\d*$/', '.XXX', $ip);
		
		$events_write = [];
		foreach($eventIndex as $key) {
			if(isset($dataSet->{$key}))
				$events_write[] = strip_oneLineInput($dataSet->{$key});
			else if(isset($responses->{$key}))
				$events_write[] = strip_oneLineInput($responses->{$key});
			else
				$events_write[] = '';
		}
		
		
		//*****
		//write event data:
		//*****
		addTo_writeCache(
			$write_cache,
			get_file_responses($study_id, FILENAME_EVENTS),
			"\n\"".implode('"'.CSV_DELIMITER.'"', $events_write).'"',
			$dataset_id
		);
	}
}
catch(Exception $ex) {
	return error('internal server-error');
}



foreach($write_cache as $file => $data) {
	if(file_put_contents($file, $data['cache'], FILE_APPEND | LOCK_EX)) {
		foreach($data['ids'] as $dataset_id) {
			success_lineOutput($output, $dataset_id, $output_index);
		}
	}
	else {
		report("Could not write to file '$file'. Sending error response to app.");
		foreach($data['ids'] as $dataset_id) {
			error_lineOutput($output, $dataset_id, 'Internal Server Error: Saving failed', $output_index);
		}
	}
}




function get_first_obj_key($obj) {
	foreach($obj as $key => $value) {
		return $key;
	}
	return null;
}

function addEvent_to_statistics(&$statistics, &$current, $event, $count) {
//	$start_of_day = floor(time() / ONE_DAY) * ONE_DAY;
//	$oldest_entry_time = $start_of_day - ONE_DAY*NUMBER_OF_SAVED_DAYS_IN_SERVER_STATISTICS;
//	$box = $statistics->days;
//
//	if(!isset($box->{$start_of_day})) {
//		$box->{$start_of_day} = new stdClass();
//
//		while(($key = get_first_obj_key($box)) < $oldest_entry_time && $key != null) {
//			unset($box->{$key});
//		}
//	}
//
//	$current = &$box->{$start_of_day};
	
	if(!isset($current->{$event}))
		$current->{$event} = $count;
	else
		$current->{$event} += $count;
	
	$statistics->week->{$event}[date('w')] += $count;
	$statistics->total->{$event} += $count;
}
update_serverStatistics(function(&$statistics, $values) {
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
		$oldest_entry_time = $start_of_day - ONE_DAY*NUMBER_OF_SAVED_DAYS_IN_SERVER_STATISTICS;
		$box = $statistics->days;
		
		if(!isset($box->{$start_of_day})) {
			$box->{$start_of_day} = new stdClass();
			
			while(($key = get_first_obj_key($box)) < $oldest_entry_time && $key != null) {
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
			addEvent_to_statistics($statistics, $current, 'questionnaire', $questionnaire_events);
		
		if($join_events != 0)
			addEvent_to_statistics($statistics, $current, 'joined', $join_events);
		
		return false;
	}
	else
		return true;
}, [$app_type, $app_version, $total_users, $questionnaire_events, $join_events]);


return success(json_encode([
	'states' => $output,
	'tokens' => $new_studyTokens
]))

?>
