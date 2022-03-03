<?php

const ONE_DAY = 86400; //in seconds: 60*60*24

ignore_user_abort(true);
set_time_limit(0);

require_once '../backend/autoload.php';

use backend\Base;
use backend\CreateDataSet;
use backend\Files;
use backend\Permission;
use backend\Output;
use backend\Configs;

//basic keys which are included in every questionnaire dataset
const KEYS_QUESTIONNAIRE_BASE_RESPONSES = [
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


function getStudyId() {
	return rand(1000, 9999);
}
function getQuestionnaireId() {
	return rand(10000, 99999);
}

function empty_folder($path) {
	$h_folder = opendir($path);
	if(!$h_folder)
		return false;
	while($file = readdir($h_folder)) {
		if($file != '.' && $file != '..') {
			$filename = $path.$file;
			if(is_dir($filename)) {
				if(!empty_folder($filename.'/') || !rmdir($filename))
					return false;
			}
			else {
				if(!unlink($filename))
					return false;
			}
		}
	}
	closedir($h_folder);
	return true;
}
function remove_study_from_index(&$key_index, $study_id) {
	$removeCount = 0;
	foreach($key_index as $key => $key_list) {
		if(($key_list_id = array_search($study_id, $key_list)) !== false) {
			unset($key_index[$key][$key_list_id]);
			++$removeCount;
		}
		if(!count($key_index[$key]))
			unset($key_index[$key]);
	}
	return $removeCount;
}

function write_indexAndResponses_files($study, $identifier, $new_keys) {
	//Note: When there is already data:
	// If keys are removed, they will stay in the headers
	// if keys are changed or new, they will be added at the end
	
	$study_id = (int) $study->id;
	$file_responses = Files::get_file_responses($study_id, $identifier);
	$file_index = Files::get_file_responsesIndex($study_id, $identifier);
	
	$csv_delimiter = Configs::get('csv_delimiter');
	
	if(file_exists($file_responses) && file_exists($file_index)) {
		$old_keys = unserialize(file_get_contents($file_index));
		
		//finding out if there are new headers:
		$index = [];
		foreach($new_keys as $value) {
			$index[$value] = $value;
		}
		foreach($old_keys as $value) {
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
			
			
			if(filesize($file_responsesBackup) > Configs::get('max_filesize_for_changes')) { //the file is too big to be changed on the fly. So we just create a new file
				write_file($file_responses, '"'.implode('"'.$csv_delimiter.'"', $new_keys).'"');
				write_file($file_index, serialize($new_keys) .',');
				Base::freeze_study($study_id, false);
				return;
			}
			
			
			//Preparing new header adding new keys to $old_key and preparing and $addedContent
			$addedContent = '';
			foreach($index as $value) {
				$addedContent .= $csv_delimiter .'""';
				$old_keys[] = $value;
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
				fputs($handle_newResponses, '"'.implode('"'.$csv_delimiter.'"', $new_keys).'"');
				write_file($file_index, serialize($new_keys) .',');
				unlink($file_responsesBackup); //there is no point in keeping this backup
			}
			else {
				fputs($handle_newResponses, '"'.implode('"'.$csv_delimiter.'"', $old_keys).'"');
				
				while(($line = fgets($handle_backup)) !== false) {
					fputs($handle_newResponses, "\n".rtrim($line, "\n").$addedContent);
				}
				
				write_file($file_index, serialize($old_keys) .',');
			}
			
			
			fflush($handle_newResponses);
			flock($handle_newResponses, LOCK_UN);
			fclose($handle_newResponses);
			fclose($handle_backup);
			Base::freeze_study($study_id, false);
		}
	}
	else {
		write_file($file_responses, '"'.implode('"'.$csv_delimiter.'"', $new_keys).'"');
		write_file($file_index, serialize($new_keys) .',');
	}
}

function get_conditionString($key, $storageType, $timeInterval, $conditions) {
	$a = [];
	foreach($conditions as $c) {
		array_push($a, $c->key .$c->operator .$c->value);
	}
	sort($a);
	return $key .$storageType .$timeInterval .implode('', $a);
}
function write_statistics($study) {
	$study_id = $study->id;
	if($study->publicStatistics->observedVariables !== new stdClass()) { //check if empty
		$folder_statistics = Files::get_folder_statistics($study_id);
		$file_statisticsMetadata = Files::get_file_statisticsMetadata($study_id);
		$file_statisticsJson = Files::get_file_statisticsJson($study_id);
		
		if(!file_exists($folder_statistics))
			create_folder($folder_statistics);
		
		$old_index = [];
		if(file_exists($file_statisticsJson)) {
			$old_statisticMetadata = unserialize(file_get_contents($file_statisticsMetadata));
			$old_statisticJson = json_decode(file_get_contents($file_statisticsJson));
			if(!empty($old_statisticJson)) {
				foreach($old_statisticJson as $value => $jsonKeyBox) {
					foreach($jsonKeyBox as $index => $jsonEntry) {
						$metadataEntry = $old_statisticMetadata->{$value}[$index];
						$old_index[get_conditionString($value, $jsonEntry->storageType, $metadataEntry->defaultTimeInterval, $metadataEntry->conditions)] = $jsonEntry;
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
				
				
				$conditionString = get_conditionString($value, $observedEntry->storageType, $observedEntry->timeInterval, $observedEntry->conditions);
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
		write_file($file_statisticsMetadata, serialize($statistics_metadata));
		write_file($file_statisticsJson, json_encode($statistics_json));
	}
}
function checkUnique_and_collectKeys($study) {
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
				$internalId = getQuestionnaireId();
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
		$keys_questionnaire = KEYS_QUESTIONNAIRE_BASE_RESPONSES; //Note: php always creates copies, which is what we need right now
		
		//make sure input and sumScore names are unique:
		if(isset($questionnaire->pages)) {
			foreach($questionnaire->pages as $page) {
				foreach($page->inputs as $input) {
					$responseType = isset($input->responseType) ? $input->responseType : 'text_input';
					
					$name = $input->name;
					
					switch($responseType) {
						case 'text':
							continue 2;
						case 'dynamic_input':
							$keys_questionnaire[] = $name .'~index';
							break;
						case 'app_usage':
							$keys_questionnaire[] = $name .'~usageTime';
							$keys_questionnaire[] = $name .'~visibleTime';
							$keys_questionnaire[] = $name .'~usageCount';
							$keys_questionnaire[] = $name .'~todayUsageTime';
							$keys_questionnaire[] = $name .'~todayVisibleTime';
							$keys_questionnaire[] = $name .'~todayUsageCount';
							$keys_questionnaire[] = $name .'~yesterdayUsageTime';
							$keys_questionnaire[] = $name .'~yesterdayVisibleTime';
							$keys_questionnaire[] = $name .'~yesterdayUsageCount';
							break;
					}
					
					if(!strlen($name))
						Output::error('Input name is empty!');
					else if(!Base::check_input($name))
						Output::error("No special characters are allowed in Variable-Names. \n'$name' detected in questionnaire: $questionnaire_title");
					else if(isset($key_check_array[$name]))
						Output::error("Variable-Name exists more than once: '$name'. First detected in questionnaire: '".$key_check_array[$input->name]."'. Detected again in questionnaire: '$questionnaire_title'");
					else if(in_array($name, KEYS_EVENT_RESPONSES) || in_array($name, KEYS_QUESTIONNAIRE_BASE_RESPONSES))
						Output::error("Protected Variable-Name: $name \nPlease choose another Variable-Name.\nDetected in questionnaire: $questionnaire_title");
					else
						$key_check_array[$name] = $questionnaire_title;
					
					$keys_questionnaire[] = $name;
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
		
		$keys_questionnaire_array[$i] = $keys_questionnaire; // used for responses index below
	}
	return $keys_questionnaire_array;
}

function write_serverConfigs($newValues) {
	$saveValues = array_merge(Configs::getDefaultAll(), Configs::getAll(), $newValues);
	
	return write_file(Files::FILE_CONFIG, '<?php return '.var_export($saveValues, true) .';');
}
function assemble_data_folderPath($data_location) {
	$last_char = substr($data_location, -1);
	if($last_char !== '/' && $last_char !== '\\')
		$data_location .= '/';
	
	if(!file_exists($data_location))
		Output::error('The path you provided does not exist on the server');
	
	return $data_location .Files::FILENAME_DATA .'/';
}


function check_userExists($user) {
	
	if(!($h = fopen(Files::get_file_logins(), 'r')))
		return false;
	while(!feof($h)) {
		$line = fgets($h);
		$data = explode(':', $line);
		
		if(!$data || sizeof($data) < 1)
			continue;
		
		if($data[0] == $user) {
			fclose($h);
			return true;
		}
	}
	fclose($h);
	return false;
}

function removeAdd_in_loginsFile($user, $new_user=null, $new_pass=null) {
	$export = '';
	if(!($h = fopen(Files::get_file_logins(), 'r')))
		return false;
	$userExists = false;
	while(!feof($h)) {
		$line = fgets($h);
		$data = explode(':', $line);
		
		if(!$data || sizeof($data) < 1)
			continue;
		
		if($data[0] != $user)
			$export .= $line;
		else {
			$userExists = true;
			if($new_user || $new_pass)
				$export .= ($new_user ?: $user).':'.($new_pass ? "$new_pass\n" : $data[1]);
		}
	}
	fclose($h);
	if($userExists) {
		write_file(Files::get_file_logins(), $export);
		return true;
	}
	return false;
}

function create_folder($folder) {
	mkdir($folder, 0775);
	chmod($folder, 0775);
}
function write_file($file, $s) {
	if(!file_put_contents($file, $s, LOCK_EX)) {
		Output::error('Writing the file \'' . $file . '\' failed');
		return false;
	}
	else {
		chmod($file, 0666);
		return true;
	}
}

function checkLoginPost() {
	if(!isset($_POST['user']) || !isset($_POST['pass']))
		return false;
	$user = $_POST['user'];
	$pass = $_POST['pass'];
	
	$blockTime = 0;
	if(!Permission::check_login($user, $pass, $blockTime)) {
		if($blockTime != 0)
			Output::error("Please wait for $blockTime seconds.");
		else
			Output::error('Wrong password');
		return false;
	}
	
	Permission::set_loggedIn($user);
	return true;
}



if(!isset($_GET['type']))
	Output::error('No data');
$type = $_GET['type'];

//is not logged in
switch($type) {
	case 'init_esmira_prep':
		if(Base::is_init())
			Output::error('Disabled');
		
		Output::successObj([
			'dir_base' => DIR_BASE,
			'dataFolder_exists' => file_exists(assemble_data_folderPath(DIR_BASE))
		]);
		break;
	case 'data_folder_exists':
		$dataFolder_path = assemble_data_folderPath($_POST['data_location']);
		
		$output = ['dataFolder_exists' => file_exists($dataFolder_path)];
		Output::successObj($output);
		
	case 'init_esmira':
		if(Base::is_init())
			Output::error('Disabled');
		else {
			$user = $_POST['new_user'];
			$pass = $_POST['pass'];
			$reuseFolder = isset($_POST['reuseFolder']) && $_POST['reuseFolder'];
			
			$dataFolder_path = assemble_data_folderPath($_POST['data_location']);
			
			//
			//create configs file
			//
			write_serverConfigs(['dataFolder_path' => $dataFolder_path]);
			Configs::reload();
			
			//
			// check if data folder already exists
			//
			if(file_exists($dataFolder_path)) {
				if($reuseFolder) { //needs to happen after configs file has been written
					if(check_userExists($user))
						removeAdd_in_loginsFile($user); //below, we add the user with the correct password again
				}
				else {
					$count = 2;
					
					do {
						$newPath = substr($dataFolder_path, 0, -1) .$count;
						
						if(++$count > 100)
							Output::error('Too many copies of ' .Files::FILE_CONFIG .' exist');
					} while(file_exists($newPath));
					
					rename($dataFolder_path, $newPath);
					
					create_folder($dataFolder_path);
				}
			}
			else
				create_folder($dataFolder_path);
			
			//
			//prepare data folder:
			//
			write_file($dataFolder_path .'.htaccess', 'Deny from all');
			
			if(!file_exists(Files::get_folder_errorReports()))
				create_folder(Files::get_folder_errorReports());
			if(!file_exists(Files::get_folder_legal()))
				create_folder(Files::get_folder_legal());
			if(!file_exists(Files::get_folder_tokenRoot()))
				create_folder(Files::get_folder_tokenRoot());
			
			if(!file_exists(Files::get_folder_studies()))
				create_folder(Files::get_folder_studies());
			if(!file_exists(Files::get_file_studyIndex()))
				write_file(Files::get_file_studyIndex(), serialize([]));
			
			
			//
			//create login:
			//
			if(!file_put_contents(Files::get_file_logins(), $user .':' .Permission::get_hashed_pass($pass) ."\n", FILE_APPEND))
				Output::error('Login data could not be saved');
			
			//
			//create permissions file:
			//
			if(file_exists(Files::get_file_permissions())) {
				$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
				if(!$permissions)
					$permissions = [];
				
				if(!isset($permissions[$user]))
					$permissions[$user] = ['admin' => true];
				else
					$permissions[$user]['admin'] = true;
			}
			else
				$permissions = [$user => ['admin' => true]];
			
			write_file(Files::get_file_permissions(), serialize($permissions));
			
			
			//
			//login:
			//
			Permission::set_loggedIn($user);
			goto get_permissions;
		}
		
		Output::error('Unknown error');
		break;
	case 'login':
		if(!isset($_POST['user']) || !isset($_POST['pass']))
			Output::error('Missing data');
		if(checkLoginPost()) {
			$user = $_POST['user'];
			if(isset($_POST['rememberMe']))
				Permission::create_token($user);
		}
		goto get_permissions;
	case 'logout':
		Permission::set_loggedOut();
		Output::successObj();
		break;
	case 'get_permissions':
		get_permissions:
		if(!Base::is_init())
			Output::successObj(['init_esmira' => true]);
		else if(!Permission::is_loggedIn())
			Output::successObj(['isLoggedIn' => false]);
		else {
			function list_additionalPermissions($is_admin, &$userPermissions) {
				$new_messages = [];
				$needsBackup = [];
				$lastActivities = [];
				$count = 0;
				$h_folder = opendir(Files::get_folder_studies());
				$writePermissions = !$is_admin && isset($userPermissions['write']) ? $userPermissions['write'] : [];
				$msgPermissions = !$is_admin && isset($userPermissions['msg']) ? $userPermissions['write'] : [];
				while($study_id = readdir($h_folder)) {
					if($study_id[0] === '.' || $study_id === Files::FILENAME_STUDY_INDEX)
						continue;
					
					//new messages:
					if($is_admin || in_array($study_id, $msgPermissions)) {
						$studyDir = Files::get_folder_messages_unread($study_id);
						if(!file_exists($studyDir))
							continue;
						$h_study = opendir($studyDir);
						while($file = readdir($h_study)) {
							if($file[0] != '.') {
								$new_messages[$study_id] = true;
								++$count;
								break;
							}
						}
					}
					
					//need backup:
					if($is_admin || in_array($study_id, $writePermissions)) {
						$metadata_path = Files::get_file_studyMetadata($study_id);
						if(file_exists($metadata_path)) {
							$metadata = unserialize(file_get_contents($metadata_path));
							if(isset($metadata['published']) && $metadata['published'] && (!isset($metadata['lastBackup']) || Base::get_milliseconds() - $metadata['lastBackup'] > Configs::get('backup_interval_days') * 24*60*60*1000)) {
								array_push($needsBackup, (int) $study_id);
							}
						}
					}
					
					//last activity:
					$events_path = Files::get_file_responses($study_id, Files::FILENAME_EVENTS);
					if(file_exists($events_path))
						array_push($lastActivities, [(int) $study_id, filemtime($events_path)]);
				}
				closedir($h_folder);
				$new_messages['count'] = $count;
				
				
				$userPermissions['new_messages'] = $new_messages;
				$userPermissions['needsBackup_list'] = $needsBackup;
				$userPermissions['lastActivities'] = $lastActivities;
			}
			
			if(Permission::is_admin()) {
				$obj = ['is_admin' => true];
				$has_errors = false;
				$msg = [];
				$h_folder = opendir(Files::get_folder_errorReports());
				while($file = readdir($h_folder)) {
					if($file[0] != '_' && $file[0] != '.') {
						$has_errors = true;
					}
				}
				closedir($h_folder);
				$obj['has_errors'] = $has_errors;
				
				list_additionalPermissions(true, $obj);
			}
			else {
				$obj = ['permissions' => Permission::get_permissions()];
				list_additionalPermissions(false, $obj);
			}
			$obj['username'] = Permission::get_user();
			$obj['isLoggedIn'] = true;
			$obj['loginTime'] = time();
			Output::successObj($obj);
		}
		break;
}

checkLoginPost();
if(!Permission::is_loggedIn() || !Base::is_init())
	Output::error('No permission');


$study_id = isset($_POST['study_id']) ? (int) $_POST['study_id'] : (isset($_GET['study_id']) ? (int) $_GET['study_id'] : 0);

$is_admin = Permission::is_admin();

//is logged in or read permission:
switch($type) {
	case 'change_password':
		if(!isset($_POST['new_pass']))
			Output::error('Unexpected data');
		
		$pass = $_POST['new_pass'];
		
		if($is_admin && isset($_POST['user']))
			$user = $_POST['user'];
		else
			$user = Permission::get_user();
		
		if(strlen($pass) < 12)
			Output::error('The password needs to have at least 12 characters.');
		
		if(removeAdd_in_loginsFile($user, null, Permission::get_hashed_pass($pass)))
			Output::successObj();
		else
			Output::error('User does not exist.');
		break;
	case 'change_username':
		if(!isset($_POST['new_user']))
			Output::error('Unexpected data');
		
		if($is_admin && isset($_POST['user']))
			$user = $_POST['user'];
		else
			$user = Permission::get_user();
		
		$new_user = $_POST['new_user'];
		
		if(check_userExists($new_user))
			Output::error("Username '$new_user' already exists");
		
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		if($permissions) {
			if(isset($permissions[$user])) {
				$p = $permissions[$new_user] = $permissions[$user];
				unset($permissions[$user]);
			}
			
			write_file(Files::get_file_permissions(), serialize($permissions));
		}
		removeAdd_in_loginsFile($user, $new_user);
		
		$folder_token = Files::get_folder_token($user);
		if(file_exists($folder_token))
			rename($folder_token, Files::get_folder_token($new_user));
		
		if(Permission::get_user() == $user) {
			$_SESSION['user'] = $new_user;
			if(isset($_COOKIE['user']))
				Base::create_cookie('user', $_COOKIE['user'] = $new_user, time()+31536000);
		}
		
		Output::successObj();
		break;
	case 'get_tokenList':
		get_tokenList:
		$user = Permission::get_user();
		$folder_token = Files::get_folder_token($user);
		$currentToken = Permission::get_currentToken();
		
		$obj = [];
		if(file_exists($folder_token)) {
			$h_folder = opendir($folder_token);
			while($file = readdir($h_folder)) {
				if($file[0] != '.')
					array_push($obj, ['hash' => $file, 'last_used' => filemtime($folder_token.$file), 'current' => ($file === $currentToken)]);
			}
			closedir($h_folder);
		}
		
		Output::successObj($obj);
		break;
	case 'get_loginHistory':
		$user = Permission::get_user();
		
		$file_history1 = Files::get_file_tokenHistory($user, 1);
		$file_history2 = Files::get_file_tokenHistory($user, 2);
		$exists1 = file_exists($file_history1);
		$exists2 = file_exists($file_history2);
		
//		header('Content-Length: ' .(($exists1 ? filesize($file_history1) : 0) + ($exists2 ? filesize($file_history2) : 0)));
		header('Content-Type: text/csv');
		$csv_delimiter = Configs::get('csv_delimiter');
		echo 'date'.$csv_delimiter.'login'.$csv_delimiter.'ip'.$csv_delimiter.'userAgent';
		if($exists1 && $exists2) {
			if(filemtime($file_history1) < filemtime($file_history2)) {
				readfile($file_history1);
				readfile($file_history2);
			}
			else {
				readfile($file_history2);
				readfile($file_history1);
			}
		}
		else if($exists1)
			readfile($file_history1);
		else if($exists2)
			readfile($file_history2);
		exit();
	case 'remove_token':
		$user = Permission::get_user();
		$token_id = $_POST['token_id'];
		Permission::remove_token($user, $token_id);
		
		goto get_tokenList;
	case 'get_new_id':
		$forQuestionnaire = $_GET['for'] === 'questionnaire';
		$filtered = $forQuestionnaire ? json_decode(file_get_contents('php://input')) : [];
		
		$study_index = file_exists(Files::get_file_studyIndex()) ? unserialize(file_get_contents(Files::get_file_studyIndex())) : [];
		
		$i = 0;
		do {
			$id = $forQuestionnaire ? getQuestionnaireId() : getStudyId();
			
			if(++$i > 1000)
				Output::error('Could not find an unused id...');
		} while(file_exists(Files::get_folder_study($id)) || isset($study_index["~$id"]) || isset($filtered[$id]));
		Output::successObj($id);
		break;
	case 'list_participants':
		$usernames_folder = Files::get_folder_userData($study_id);
		$usernames = [];
		if(file_exists($usernames_folder)) {
			$h_folder = opendir($usernames_folder);
			while($file = readdir($h_folder)) {
				if($file[0] != '.') {
					$usernames[] = Files::get_urlFriendly($file);
				}
			}
			closedir($h_folder);
		}
		Output::successObj($usernames);
		break;
	case 'list_data':
		if(!$is_admin && !Permission::has_permission($study_id, 'read'))
			Output::error('No permission');
		
		$l_folder = opendir(Files::get_folder_responses($study_id));
		
		$msg = [];
		$events_file = Files::FILENAME_EVENTS.'.csv';
		$webAccess_file = Files::FILENAME_WEB_ACCESS.'.csv';
		while($file = readdir($l_folder)) {
			if($file[0] != '.' && $file != $events_file && $file != $webAccess_file) {
				$msg[] = substr($file, 0, -4);
			}
		}
		Output::successObj($msg);
		break;
	case 'get_data':
		if(!$is_admin && !Permission::has_permission($study_id, 'read'))
			Output::error('No permission');
		$file_responses = Files::get_file_responses($study_id, $_GET['q_id']);
		if(file_exists($file_responses)) {
//			header('Content-Length: ' .filesize($file_responses));
			header('Content-Type: text/csv');
			readfile($file_responses);
			exit();
		}
		else
			Output::error('Not found');
		break;
}

//has msg permission:
if($study_id != 0 && ($is_admin || Permission::has_permission($study_id, 'msg'))) {
	switch($type) {
		case 'send_message':
			function send_message($study_id, $from, $user, $content) {
				if(!strlen($user))
					return false;
				$msg = [
					'from' => $from,
					'content' => $content,
					'sent' => Base::get_milliseconds(),
					'pending' => true,
					'delivered' => 0
				];
				
				$file = Files::get_file_message_pending($study_id, $user);
				
				if(file_exists($file)) {
					$messages = unserialize(file_get_contents($file));
					array_push($messages, $msg);
				}
				else
					$messages = [$msg];
				
				return write_file($file, serialize($messages));
			}
			
			$json = json_decode(file_get_contents('php://input'));
			
			if(!$json)
				Output::error('Input is faulty');
			
			$from = Permission::get_user();
			$content = $json->content;
			$toAll = $json->toAll;
			
			
			if(strlen($content) < 2)
				Output::error("Message is too short");
			
			if($json->toAll) {
				$appVersion = $json->appVersion;
				$appType = isset($json->appType) ? $json->appType : false;
				$checkUserdata = $appVersion || $appType;
				
				
				$usernames_folder = Files::get_folder_userData($study_id);
				$count = 0;
				if(file_exists($usernames_folder)) {
					$h_folder = opendir($usernames_folder);
					while($file = readdir($h_folder)) {
						if($file[0] != '.') {
							$user = Files::get_urlFriendly($file);
							if($checkUserdata) {
								$userdata = unserialize(file_get_contents($usernames_folder.$file));
								if(($appVersion && $userdata['appVersion'] != $appVersion) || ($appType &&$userdata['appType'] != $appType))
									continue;
							}
							++$count;
							if(!send_message($study_id, $from, $user, $content))
								Output::error("Could not save message for $user. $count messages have already been sent. Aborting now...");
						}
					}
					closedir($h_folder);
				}
			}
			else {
				$user = $json->user;
				if(!Base::check_input($user))
					Output::error('Recipient is faulty');
				
				if(!send_message($study_id, $from, $user, $content))
					Output::error("Could not save message");
			}
			goto messages_setRead;
		case 'delete_message':
			$user = $_POST['user'];
			$sent = $_POST['sent'];
			$msgs_pending_folder = Files::get_folder_messages_pending($study_id);
			
			$file_pending = Files::get_file_message_pending($study_id, $user);
			if(!file_exists($file_pending))
				Output::error('Message does not exist');
			
			$changeMessages = unserialize(file_get_contents($file_pending));
			
			foreach($changeMessages as $index => $cMsg) {
				if($cMsg['sent'] == $sent) {
					array_splice($changeMessages, $index, 1);
					break;
				}
			}
			
			if(count($changeMessages) === 0) {
				if(unlink($file_pending))
					Output::successObj([]);
				else
					Output::error("Could not delete $file_pending");
			}
			else if(write_file($file_pending, serialize($changeMessages)))
				Output::successObj($changeMessages);
			else
				Output::error("Could not save message");
			break;
		case 'list_userWithMessages':
			function indexFolder(&$index, &$msgs, $folder, $attr = false) {
				if(file_exists($folder)) {
					$h_folder = opendir($folder);
					while($file = readdir($h_folder)) {
						if($file[0] != '.') {
							$username = Files::get_urlFriendly($file);
							if(!isset($index[$username])) {
								$index[$username] = true;
								$newMsg = [
									'name' => $username,
									'lastMsg' => filemtime($folder .$file) * 1000
								];
								if($attr) {
									$newMsg[$attr] = true;
								}
								$msgs[] = $newMsg;
							}
						}
					}
					closedir($h_folder);
				}
			}
			
			$msgs_archive_folder = Files::get_folder_messages_archive($study_id);
			$msgs_pending_folder = Files::get_folder_messages_pending($study_id);
			$msgs_unread_folder = Files::get_folder_messages_unread($study_id);
			
			$changeMessages = [];
			$index = [];
			if(file_exists($msgs_unread_folder))
				indexFolder($index, $changeMessages, $msgs_unread_folder, 'unread');
			if(file_exists($msgs_pending_folder))
				indexFolder($index, $changeMessages, $msgs_pending_folder, 'pending');
			if(file_exists($msgs_archive_folder))
				indexFolder($index, $changeMessages, $msgs_archive_folder);
			
			
			Output::successObj($changeMessages);
			break;
		case 'list_messages':
			$user = $_GET['user'];
			if(!Base::check_input($user))
				Output::error('Username is faulty');
			
			if(!strlen($user)) {
				$changeMessages = [
					'archive' => [],
					'pending' => [],
					'unread' => []
				];
			}
			else {
				$file_archive = Files::get_file_message_archive($study_id, $user);
				$file_pending = Files::get_file_message_pending($study_id, $user);
				$file_unread = Files::get_file_message_unread($study_id, $user);
				
				$changeMessages = [
					'archive' => file_exists($file_archive) ? unserialize(file_get_contents($file_archive)) : [],
					'pending' => file_exists($file_pending) ? unserialize(file_get_contents($file_pending)) : [],
					'unread' => file_exists($file_unread) ? unserialize(file_get_contents($file_unread)) : []
				];
			}
			
			Output::successObj($changeMessages);
			break;
		case 'messages_setRead':
			messages_setRead:
			if(!isset($json))
				$json = json_decode(file_get_contents('php://input'));
			
			$changeMessages = $json->timestamps;
			$user = $json->user;
			
			$file_unread = Files::get_file_message_unread($study_id, $user);
			if(!file_exists($file_unread))
				Output::successObj();
			
			$handle_unread = fopen($file_unread, 'r+');
			if(!$handle_unread)
				Output::error("Could not open $file_unread");
			flock($handle_unread, LOCK_EX);
			$messages_unread = unserialize(fread($handle_unread, filesize($file_unread)));
			
			
			
			$file_archive = Files::get_file_message_archive($study_id, $user);
			if(file_exists($file_archive)) {
				$handle_archive = fopen($file_archive, 'r+');
				if(!$handle_archive) {
					flock($handle_unread, LOCK_UN);
					fclose($handle_unread);
					Output::error("Could not open $file_archive");
				}
				$messages_archive = unserialize(fread($handle_archive, filesize($file_archive)));
				
				fseek($handle_archive, 0);
				if(!ftruncate($handle_archive, 0)) {
					flock($handle_unread, LOCK_UN);
					fclose($handle_unread);
					fclose($handle_archive);
					Output::error("Could not empty $file_archive");
				}
			}
			else {
				if(!($handle_archive = fopen($file_archive, 'w'))) {
					flock($handle_unread, LOCK_UN);
					fclose($handle_unread);
					Output::error("Could not open $file_archive");
				}
				$messages_archive = [];
			}
			flock($handle_archive, LOCK_EX);
			
			
			foreach($changeMessages as $timestamp) {
				foreach($messages_unread as $index => $msg) {
					if($msg['sent'] == $timestamp) {
						unset($msg['unread']);
						$messages_archive[] = $msg;
						unset($messages_unread[$index]);
						break;
					}
				}
			}
			
			
			$error = false;
			if(count($messages_unread)) {
				fseek($handle_unread, 0);
				if(!ftruncate($handle_unread, 0))
					$error = "Could not empty $file_unread";
				else if(!fwrite($handle_unread, serialize($messages_unread)))
					$error = "Could not write to $file_unread";
			}
			else if(!unlink($file_unread))
				$error = "Could not delete $file_unread";
			
			
			if(!$error && !fwrite($handle_archive, serialize($messages_archive)))
				$error = "Could not write to $file_archive";
			
			
			fflush($handle_unread);
			fflush($handle_archive);
			flock($handle_unread, LOCK_UN);
			flock($handle_archive, LOCK_UN);
			fclose($handle_unread);
			fclose($handle_archive);
			
			if($error)
				Output::error($error);
			else
				Output::successObj();
			
			break;
	}
}

//has write permission:
if($study_id != 0 && ($is_admin || Permission::has_permission($study_id, 'write'))) {
	switch($type) {
		case 'empty_data':
			$responses_folder = Files::get_folder_responses($study_id);
			if(file_exists($responses_folder))
				empty_folder($responses_folder);
			else
				Output::error("$responses_folder does not exist");
			
			
			//delete statistics
			$statistics_folder = Files::get_folder_statistics($study_id);
			if(file_exists($statistics_folder))
				empty_folder($statistics_folder);
			else
				Output::error("$statistics_folder does not exist");
			
			//recreate study
			$study_file = Files::get_file_studyConfig($study_id);
			if(file_exists($study_file))
				$study_json = file_get_contents($study_file);
			else
				Output::error("$study_file does not exist");
			
			
			if(!($study = json_decode($study_json)))
				Output::error('Unexpected data');
			
			$keys = checkUnique_and_collectKeys($study);
			foreach($study->questionnaires as $i => $q) {
				write_indexAndResponses_files($study, $q->internalId, $keys[$i]);
			}
			write_indexAndResponses_files($study, Files::FILENAME_EVENTS, KEYS_EVENT_RESPONSES);
			write_indexAndResponses_files($study, Files::FILENAME_WEB_ACCESS, KEYS_WEB_ACCESS);
			write_statistics($study);
			
			Output::successObj();
			break;
		case 'check_changed':
			$sentChanged = (int) $_GET['lastChanged'];
			$file_config = Files::get_file_studyConfig($study_id);
			
			if(!file_exists($file_config))
				Output::error('Study does not exist');
			
			
			$realChanged = filemtime($file_config);
			if($realChanged > $sentChanged) {
				$study = file_get_contents($file_config);
				Output::successObj(['lastChanged' => $realChanged, 'json' => $study]);
			}
			else
				Output::successObj(['lastChanged' => $realChanged]);
			
			break;
		case 'load_langs':
			$folder_langs = Files::get_folder_langs($study_id);
			$langObj = [];
			$langString = "{";
			if(file_exists($folder_langs)) {
				$h_folder = opendir($folder_langs);
				while($file = readdir($h_folder)) {
					if($file[0] != '.') {
						$s = file_get_contents($folder_langs .$file);
//						$langObj[explode('.', $file)[0]] =  $s;
						$langString .= '"' .explode('.', $file)[0] .'":' .$s;
					}
				}
				closedir($h_folder);
			}
//			Output::successObj($langObj);
			Output::successString($langString .'}');
			break;
		case 'save_study':
			$studyCollection_json = file_get_contents('php://input');
			
			if(!($studyCollection = json_decode($studyCollection_json)))
				Output::error('Unexpected data');
			
			if(!isset($studyCollection->_))
				Output::error('No default study');
			
			$study = $studyCollection->_;
			
			if(!isset($study->id) || $study->id != $study_id)
				Output::error("Problem with study id! $study_id !=" .$study->id);
			
			$file_config = Files::get_file_studyConfig($study_id);
			
			if(isset($_GET['lastChanged']) && file_exists($file_config) && filemtime($file_config) > $_GET['lastChanged'])
				Output::error('The study configuration was changed (by another user?) since you last loaded it. You can not save your changes. Please reload the page.');
			
			
			$study_index = file_exists(Files::get_file_studyIndex()) ? unserialize(file_get_contents(Files::get_file_studyIndex())) : [];
			
			//*****
			//check and prepare questionnaires:
			//*****
			
			$keys_questionnaire_array = checkUnique_and_collectKeys($study);
			
			
			//*****
			//create folders
			//*****
			
			$folder_study = Files::get_folder_study($study_id);
			$folder_langConfigs = Files::get_folder_langs($study_id);
			$folder_study_responses = Files::get_folder_responses($study_id);
			$folder_study_messages = Files::get_folder_messages($study_id);
			$folder_study_messages_user = Files::get_folder_messages_archive($study_id);
			$folder_study_messages_outgoing = Files::get_folder_messages_pending($study_id);
			$folder_study_messages_unread = Files::get_folder_messages_unread($study_id);
			$folder_study_responsesIndex = Files::get_folder_responsesIndex($study_id);
			$folder_study_statistics = Files::get_folder_statistics($study_id);
			$folder_study_token = Files::get_folder_userData($study_id);
			
			if(!file_exists($folder_study))
				create_folder($folder_study);
			
			if(!file_exists($folder_langConfigs))
				create_folder($folder_langConfigs);
			
			if(!file_exists($folder_study_token))
				create_folder($folder_study_token);
			
			if(!file_exists($folder_study_messages))
				create_folder($folder_study_messages);
			
			if(!file_exists($folder_study_messages_user))
				create_folder($folder_study_messages_user);
			
			if(!file_exists($folder_study_messages_outgoing))
				create_folder($folder_study_messages_outgoing);
			
			if(!file_exists($folder_study_messages_unread))
				create_folder($folder_study_messages_unread);
			
			if(!file_exists($folder_study_responses))
				create_folder($folder_study_responses);
			
			if(!file_exists($folder_study_responsesIndex))
				create_folder($folder_study_responsesIndex);
			
			if(!file_exists($folder_study_statistics))
				create_folder($folder_study_statistics);
			
			
			//*****
			//save questionnaire index (has to happen after folders are created)
			//*****
			
			foreach($study->questionnaires as $i => $q) {
				write_indexAndResponses_files($study, $q->internalId, $keys_questionnaire_array[$i]);
			}
			
			
			//*****
			//Creating observable variables and statistics
			//*****
			
			
			function check_axis(&$axisData, &$index, &$observed_variables, $storageType, $timeInterval) {
				if(!isset($axisData))
					return;
				
				$key = isset($axisData->variableName) ? $axisData->variableName : '';
				if(!strlen($key))
					return;
				
				$conditionString = get_conditionString($key, $storageType, $timeInterval, $axisData->conditions);
				if(isset($index[$conditionString])) {
					$axisData->observedVariableIndex = $index[$conditionString];
				}
				else {
					if(!isset($observed_variables->{$key})) {
						$observed_variables->{$key} = [];
					}
					$keyBox = &$observed_variables->{$key};
					$conditionType = isset($axisData->conditionType) ? $axisData->conditionType : CreateDataSet::CONDITION_TYPE_ALL;
					$obj = (object)['conditions' => $axisData->conditions, 'conditionType' => $conditionType, 'storageType' => $storageType, 'timeInterval' => $timeInterval];
					
					array_push($keyBox, $obj);
					$index[$conditionString] = $axisData->observedVariableIndex = sizeof($keyBox)-1;
				}
			}
//			function set_observedVariables_from_axis(&$statistics_configs, &$public_observed_variables=null, &$public_index=null) {
			function set_observedVariables_from_axis(&$studyCollection, $configName, &$public_observed_variables=null, &$public_index=null) {
				$langConfigs = [];
				$defaultConfig = $studyCollection->_->{$configName};
				foreach($studyCollection as $code => &$study) {
					if($code !== '_')
						$langConfigs[$code] = &$study->{$configName};
				}
				
				$observedVariables = new stdClass; //new stdClass translates into an empty object (instead of array) in JSON
				$index = [];
				
				
				foreach($defaultConfig->charts as $chart_i => &$defaultChart) {
					$dataType = isset($defaultChart->dataType) ? number_format($defaultChart->dataType) : CreateDataSet::STATISTICS_DATATYPES_DAILY;
					switch($dataType) {
						case CreateDataSet::STATISTICS_DATATYPES_SUM:
						case CreateDataSet::STATISTICS_DATATYPES_DAILY:
							$timeInterval = ONE_DAY;
							$storageType = CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED;
							break;
						case CreateDataSet::STATISTICS_DATATYPES_XY:
						default:
							$timeInterval = Configs::get('smallest_timed_distance');
							$storageType = CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED;
							break;
						case CreateDataSet::STATISTICS_DATATYPES_FREQ_DISTR:
							$timeInterval = 0;
							$storageType = CreateDataSet::STATISTICS_STORAGE_TYPE_FREQ_DISTR;
							break;
					}
					
					
					foreach($defaultChart->axisContainer as $axis_i => &$defaultAxisContainer) {
						check_axis($defaultAxisContainer->yAxis, $index, $observedVariables, $storageType, $timeInterval);
						
						if($dataType == CreateDataSet::STATISTICS_DATATYPES_XY)
							check_axis($defaultAxisContainer->xAxis, $index, $observedVariables, $storageType, $timeInterval);
						else
							$defaultAxisContainer->xAxis->observedVariableIndex = -1;
						
						foreach($langConfigs as &$config) {
							$langAxis = &$config->charts[$chart_i]->axisContainer[$axis_i];
							$langAxis->yAxis->observedVariableIndex = $defaultAxisContainer->yAxis->observedVariableIndex;
							$langAxis->xAxis->observedVariableIndex = $defaultAxisContainer->xAxis->observedVariableIndex;
						}
					}
					
					if(isset($defaultChart->displayPublicVariable) && $defaultChart->displayPublicVariable && $public_observed_variables) {
						foreach($defaultChart->publicVariables as $axis) {
							check_axis($axis->yAxis, $public_index, $public_observed_variables, $storageType, $timeInterval);
							if($dataType == CreateDataSet::STATISTICS_DATATYPES_XY)
								check_axis($axis->xAxis, $public_index, $public_observed_variables, $storageType, $timeInterval);
						}
					}
					
					$defaultChart->storageType = $storageType;
				}
				
				$defaultConfig->observedVariables = $observedVariables;
				foreach($langConfigs as &$config) {
					$config->observedVariables = $observedVariables;
				}
				
				return $index;
			}
			
			//publicStatistics:
			$public_index = set_observedVariables_from_axis($studyCollection, 'publicStatistics');
			
			//personalStatistics:
			//Note: $public_index can still change when global variables are used in personal charts
			// so we need this before we save the public statistics file
			set_observedVariables_from_axis($studyCollection, 'personalStatistics', $study->publicStatistics->observedVariables, $public_index);
			
			
			//statistics files:
			write_statistics($study);
			
			
			//*****
			//saving files
			//*****
			
			//
			//publish / unpublish study
			//
			if($is_admin || Permission::has_permission($study_id, 'publish')) {
				$removeCount = remove_study_from_index($study_index, $study_id);
				
				if(isset($study->published) && $study->published) {
					//entries for accessKeys:
					if(isset($study->accessKeys) && count($study->accessKeys)) {
						foreach($study->accessKeys as $key => $value) {
							$value = strtolower($value);
							foreach($studyCollection as &$langStudy) {
								$langStudy->accessKeys[$key] = $value;
							}
							
							if(!Base::check_input($value))
								Output::error("No special characters are allowed in access keys.\n'$value'");
							else if(!preg_match("/^([a-zA-Z][a-zA-Z0-9]*)$/", $value))
								Output::error("Access keys need to start with a character.");
							else if(!isset($study_index[$value]))
								$study_index[$value] = [$study_id];
							else if(!in_array($study_id, $study_index[$value]))
								array_push($study_index[$value], $study_id);
						}
					}
					else {
						if(!isset($study_index['~open']))
							$study_index['~open'] = [$study_id];
						else
							array_push($study_index['~open'], $study_id);
					}
					
					//entries for questionnaire internalIds
					foreach($study->questionnaires as $q) {
						$key = '~'.$q->internalId;
						
						$study_index[$key] = [$study_id]; //this doesnt have to be an array. But we try to stay consistent with access key entries
					}
					
					//update server statistics:
					if(!$removeCount) {
						Base::update_serverStatistics(function(&$statistics) {
							$statistics->total->studies += 1;
						});
					}
				}
				else if($removeCount) {
					Base::update_serverStatistics(function(&$statistics) {
						$statistics->total->studies -= 1;
					});
				}
				write_file(Files::get_file_studyIndex(), serialize($study_index));
			}
			else {
				$old_study = file_exists($file_config) ? json_decode(file_get_contents($file_config)) : [];
				
				foreach($studyCollection as &$langStudy) {
					$langStudy->accessKeys = isset($old_study->accessKeys) ? $old_study->accessKeys : [];
					$langStudy->published = isset($old_study->published) ? $old_study->published : false;
				}
			}
			
			//
			//save study config
			//
			if(!isset($study->version) || $study->version === 0) {
				foreach($studyCollection as &$langStudy) {
					$langStudy->version = 1;
					$langStudy->subVersion = 0;
				}
			}
			else {
				foreach($studyCollection as &$langStudy) {
					$langStudy->new_changes = true;
					$langStudy->subVersion += 1;
				}
			}
			
			//delete old language files
			empty_folder(Files::get_folder_langs($study_id));
			
			$studies_json = [];
			foreach($studyCollection as $code => $s) {
				$study_json = json_encode($s);
				write_file($code === '_' ? $file_config : Files::get_file_langConfig($study_id, $code), $study_json);
				$studies_json[] = "\"$code\":$study_json";
			}
			
			
			//
			//create web_access and events file
			//
			write_indexAndResponses_files($study, Files::FILENAME_EVENTS, KEYS_EVENT_RESPONSES);
			write_indexAndResponses_files($study, Files::FILENAME_WEB_ACCESS, KEYS_WEB_ACCESS);
			
			
			//
			//save index-files
			//
			$metadata = Base::get_newMetadata($study);
			write_file(Files::get_file_studyMetadata($study_id), serialize($metadata));
			$sentChanged = time();
			Output::successString("{\"lastChanged\":$sentChanged,\"json\":{" .implode(',', $studies_json) ."}}");
			break;
		case 'mark_study_as_updated'://this will mark the study as updated for already existing participants
			$file = Files::get_file_studyConfig($study_id);
			if(!($study = json_decode(file_get_contents($file))))
				Output::error('Unexpected data');
			
			$study->version = isset($study->version) ? $study->version + 1 : 1;
			$study->subVersion = 0;
			$study->new_changes = false;
			
			write_file($file, json_encode($study));
			
			$metadata = Base::get_newMetadata($study);
			write_file(Files::get_file_studyMetadata($study_id), serialize($metadata));
			
			$sentChanged = time();
			Output::successObj(['lastChanged' => $sentChanged]);
			break;
		case 'backup_study':
			$study = json_decode(file_get_contents(Files::get_file_studyConfig($study_id)));
			
			$metadata_path = Files::get_file_studyMetadata($study_id);
			if(!file_exists($metadata_path))
				Output::error('Metadata file does not exist. Save the study to create it.');
			
			$metadata = unserialize(file_get_contents($metadata_path));
			
			function backup($study_id, $identifier) {
				$file_name = Files::get_file_responses($study_id, $identifier);
				$file_backupName = Files::get_file_responsesBackup($study_id, $identifier);
				
				if(!copy($file_name, $file_backupName))
					Output::error("Copying $file_name to $file_backupName failed");
			}
			foreach($study->questionnaires as $questionnaire) {
				backup($study_id, $questionnaire->internalId);
			}
			
			backup($study_id, Files::FILENAME_EVENTS);
			backup($study_id, Files::FILENAME_WEB_ACCESS);
			$metadata['lastBackup'] = Base::get_milliseconds();
			write_file($metadata_path, serialize($metadata));
			Output::successObj();
			
			break;
		case 'is_frozen':
			Output::successObj(Base::study_is_locked($study_id));
			break;
		case 'freeze_study':
			Base::freeze_study($study_id, isset($_GET['frozen']));
			Output::successObj(Base::study_is_locked($study_id));
			break;
	}
}

if(!$is_admin)
	Output::error('No permission');

//is admin:
switch($type) {
	case 'get_serverConfigs':
		$serverSettings = Configs::getAll();
		$serverSettings['impressum'] = [];
		$serverSettings['privacyPolicy'] = [];
		
		$langCodes = Configs::get('langCodes');
		array_push($langCodes, '_');
		foreach($langCodes as $code) {
			$file_impressum = Files::get_file_langImpressum($code);
			if(file_exists($file_impressum))
				$serverSettings['impressum'][$code] = file_get_contents($file_impressum);
			
			$file_privacyPolicy = Files::get_file_langPrivacyPolicy($code);
			if(file_exists($file_privacyPolicy))
				$serverSettings['privacyPolicy'][$code] = file_get_contents($file_privacyPolicy);
		}
		Output::successObj($serverSettings);
		break;
	case 'save_serverConfigs':
		$settingsCollection_json = file_get_contents('php://input');
		
		if(!($settingsCollection = json_decode($settingsCollection_json)))
			Output::error('Unexpected data');
		
		if(!isset($settingsCollection->_))
			Output::error('No default settings');
		
		$old_langCodes = Configs::get('langCodes');
		
		$serverNames = [];
		$langCodes = [];
		foreach($settingsCollection as $code => $s) {
			if($code !== '_') {
				array_push($langCodes, $code);
				if (($k = array_search($code, $old_langCodes)) !== false)
					unset($old_langCodes[$k]);
			}
			$serverName = urldecode($s->serverName);
			$impressum = urldecode($s->impressum);
			$privacyPolicy = urldecode($s->privacyPolicy);
			
			$len = strlen($serverName);
			if($len < 3 || $len > 30)
				Output::error('The server name needs to consist of 3 and 30 characters');
			else if(!Base::check_input($serverName))
				Output::error('The server name has forbidden characters');
			else
				$serverNames[$code] = $serverName;
			
			$file_impressum = Files::get_file_langImpressum($code);
			if(strlen($impressum))
				write_file($file_impressum, $impressum);
			else if(file_exists($file_impressum))
				unlink($file_impressum);
			
			$file_privacyPolicy = Files::get_file_langPrivacyPolicy($code);
			if(strlen($privacyPolicy))
				write_file($file_privacyPolicy, $privacyPolicy);
			else if(file_exists($file_privacyPolicy))
				unlink($file_privacyPolicy);
		}
		
		//if a language has been removed, we need to remove its files too:
		foreach($old_langCodes as $code) {
			$file_impressum = Files::get_file_langImpressum($code);
			if(file_exists($file_impressum))
				unlink($file_impressum);
			
			$file_privacyPolicy = Files::get_file_langPrivacyPolicy($code);
			if(file_exists($file_privacyPolicy))
				unlink($file_privacyPolicy);
		}
		
		write_serverConfigs([
			'serverName' => $serverNames,
			'langCodes' => $langCodes,
		]);
		
		Output::successObj();
		break;
	case 'delete_error':
		if(!isset($_POST['timestamp']) || !isset($_POST['seen']) || !isset($_POST['note']))
			Output::error('Faulty input');
		
		$timestamp = $_POST['timestamp'];
		$seen = $_POST['seen'];
		$note = $_POST['note'];
		
		$filename = Files::get_file_errorReport($timestamp, $note, $seen);
		
		if(file_exists($filename) && unlink($filename))
			Output::successObj();
		else
			Output::error("Could not remove $filename");
		
		break;
	case 'change_error':
		if(!isset($_POST['timestamp']) || !isset($_POST['seen']) || !isset($_POST['note']))
			Output::error('Faulty input');
		
		$timestamp = $_POST['timestamp'];
		$seen = $_POST['seen'];
		$note = $_POST['note'];
		
		$filename = Files::get_file_errorReport($timestamp, $note, $seen);
		
		if(!file_exists($filename))
			Output::error('Error report does not exist!');
		
		if(isset($_POST['new_seen']))
			$seen = $_POST['new_seen'];
		if(isset($_POST['new_note']))
			$note = $_POST['new_note'];
		
		$new_filename = Files::get_file_errorReport($timestamp, $note, $seen);
		
		if(rename($filename, $new_filename))
			Output::successObj();
		else
			Output::error("Could not change $filename");
		break;
	case 'list_errors':
		$msg = [];
		$h_folder = opendir(Files::get_folder_errorReports());
		while($file = readdir($h_folder)) {
			if($file[0] != '.') {
//				$msg[] = '"' .explode('.', $file)[0] .'"';
				$msg[] = Files::interpret_errorReport_file($file);
			}
		}
		closedir($h_folder);
//		Output::success('['.implode(',', $msg).']');
		Output::successObj($msg);
		break;
	case 'get_error':
		if(!isset($_GET['timestamp']) || !isset($_GET['seen']) || !isset($_GET['note']))
			Output::error('Faulty input');
		
		$timestamp = $_GET['timestamp'];
		$seen = $_GET['seen'];
		$note = $_GET['note'];
		
		$file_responses = Files::get_file_errorReport($timestamp, $note, $seen);
		if(file_exists($file_responses)) {
//			header('Content-Length: ' .filesize($file_responses));
			header('Content-Type: text/csv');
			readfile($file_responses);
			exit();
		}
		else
			Output::error('Not found');
		break;
	case 'delete_study':
		if($study_id == 0)
			Output::error('Missing data');
		
		//remove from study-index
		$study_index = file_exists(Files::get_file_studyIndex()) ? unserialize(file_get_contents(Files::get_file_studyIndex())) : [];
		remove_study_from_index($study_index, $study_id);
		write_file(Files::get_file_studyIndex(), serialize($study_index));
		
		
		//remove study data
		$folder_study = Files::get_folder_study($study_id);
		if(file_exists($folder_study)) {
			empty_folder($folder_study);
			if(!rmdir($folder_study))
				Output::error("Could not remove $folder_study");
		}
		else
			Output::error("$folder_study does not exist!");
		
		
		//remove from permissions
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		if($permissions) {
			if(isset($permissions['write'])) {
				foreach($permissions['write'] as $user => $studies) {
					foreach($studies as $value => $current_study_id) {
						if($current_study_id === $study_id)
							array_splice($permissions['write'][$user], $value, 1);
					}
				}
			}
			if(isset($permissions['read'])) {
				foreach($permissions['read'] as $user => $studies) {
					foreach($studies as $value => $current_study_id) {
						if($current_study_id === $study_id)
							array_splice($permissions['read'], $value, 1);
					}
				}
			}
			write_file(Files::get_file_permissions(), serialize($permissions));
		}
		
		Output::successObj($study_id);
		break;
	case 'list_users':
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		if($permissions) {
			$admins = (isset($permissions['admins'])) ? $permissions['admins'] : [];
			$read_permissions = (isset($permissions['read'])) ? $permissions['read'] : [];
			$write_permissions = (isset($permissions['write'])) ? $permissions['write'] : [];
			$publish_permissions = (isset($permissions['publish'])) ? $permissions['publish'] : [];
			$msg_permissions = (isset($permissions['msg'])) ? $permissions['msg'] : [];
		}
		else {
			$admins = [];
			$read_permissions = [];
			$write_permissions = [];
			$publish_permissions = [];
			$msg_permissions = [];
		}
		$userList = [];
		if(!($h = fopen(Files::get_file_logins(), 'r')))
			Output::error("Could not open logins file");
		
		while(!feof($h)) {
			$line = substr(fgets($h), 0, -1);
			if($line == '')
				continue;
			$data = explode(':', $line);
			$username = $data[0];
			
			if(isset($permissions[$username])) {
				$user = $permissions[$username];
				$user['username'] = $username;
				$userList[] = $user;
			}
			else {
				$userList[] = ['username' => $username];
			}
		}
		Output::successObj($userList);
		break;
	case 'create_user':
		if(!isset($_POST['new_user']) || !isset($_POST['pass']) || strlen($_POST['pass']) <= 3)
			Output::error('Unexpected data');
		
		$user = $_POST['new_user'];
		if(check_userExists($user))
			Output::error("Username '$user' already exists");
		
		$pass = Permission::get_hashed_pass($_POST['pass']);
		
		
		if(!file_put_contents(Files::get_file_logins(), $user .':' .$pass ."\n", FILE_APPEND))
			Output::error('Login data could not be saved');
		else
			Output::successObj(['username' => $user]);
		break;
	case 'delete_user':
		if(!isset($_POST['user']))
			Output::error('Unexpected data');
		
		$user = $_POST['user'];
		
		//remove permissions:
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		if($permissions) {
			if(isset($permissions[$user])) {
				$userPerms = $permissions[$user];
				unset($permissions[$user]);
			}
			
			write_file(Files::get_file_permissions(), serialize($permissions));
		}
		
		removeAdd_in_loginsFile($user);
		
		Output::successObj();
		break;
	case 'toggle_admin':
		if(!isset($_POST['user']))
			Output::error('Missing data');
		else if(Permission::get_user() === $_POST['user'])
			Output::error('You can not remove your own admin permissions');
		
		$user = $_POST['user'];
		$admin = isset($_POST['admin']);
		
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		if(!$permissions)
			$permissions = [];
		
		if(!isset($permissions[$user]))
			$permissions[$user] = ['admin' => $admin];
		else
			$permissions[$user]['admin'] = $admin;
		
		
		write_file(Files::get_file_permissions(), serialize($permissions));
		
		Output::successObj();
		break;
	case 'add_userPermission':
		if(!isset($_POST['user']) || !isset($_POST['permission']) || $study_id == 0)
			Output::error('Missing data');
		
		$user = $_POST['user'];
		$permCode = $_POST['permission'];
		
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		if(!$permissions)
			$permissions = [];
		
		function addPermission(&$permissions, $study_id, $user, $permCode) {
			if(!isset($permissions[$user]))
				$permissions[$user] = [$permCode => [$study_id]];
			else if(!isset($permissions[$user][$permCode]))
				$permissions[$user][$permCode] = [$study_id];
			else if(!in_array($study_id, $permissions[$user][$permCode]))
				$permissions[$user][$permCode][] = $study_id;
		}
		
		switch($permCode) {
			case 'read':
			case 'write':
			case 'msg':
				addPermission($permissions, $study_id, $user, $permCode);
				break;
			case 'publish':
				addPermission($permissions, $study_id, $user, 'publish');
				addPermission($permissions, $study_id, $user, 'write');
				break;
			default:
				Output::error('Faulty data');
		}
		
		write_file(Files::get_file_permissions(), serialize($permissions));
		Output::successObj();
		break;
	case 'delete_userPermission':
		if(!isset($_POST['user']) || !isset($_POST['permission']) || $study_id == 0)
			Output::error('Missing data');
		
		$user = $_POST['user'];
		$permCode = $_POST['permission'];
		
		
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		if(!$permissions)
			Output::error('No permissions to remove');
		else if(!isset($permissions[$user]))
			Output::error('User has no permissions');
		
		function removePermission(&$permissions, $study_id, $user, $permCode) {
			if(isset($permissions[$user][$permCode]) && ($value = array_search($study_id, $permissions[$user][$permCode])) !== false)
				array_splice($permissions[$user][$permCode], $value, 1);
		}
		
		switch($permCode) {
			case 'write':
				removePermission($permissions, $study_id, $user, 'write');
				removePermission($permissions, $study_id, $user, 'publish');
				break;
			case 'msg':
			case 'read':
			case 'publish':
				removePermission($permissions, $study_id, $user, $permCode);
				break;
			default:
				Output::error('Faulty data');
		}
		
		
		write_file(Files::get_file_permissions(), serialize($permissions));
		
		Output::successObj();
		break;
	case 'check_update':
		$currentVersion = $_GET['version'];
		$json=file_get_contents(Configs::get('url_update_packageInfo'));
		$version = json_decode($json)->version;
		
		if($currentVersion != $version) {
			$changelog=file_get_contents(Configs::get('url_update_changelog'));
			Output::successObj(['has_update' => true, 'newVersion' => $version, 'changelog' => $changelog]);
		}
		else
			Output::successObj(['has_update' => false]);
		break;
	case 'download_update':
		$file_update = Files::get_file_serverUpdate();
		if(file_exists($file_update))
			unlink($file_update);
		$res = fopen(Configs::get('url_update_releaseZip'), 'r');
		if(!$res)
			Output::error('Downloading update failed. Nothing was changed');
		
		if(!file_put_contents($file_update, $res))
			Output::error('Saving update file failed. Nothing was changed');
		
		Output::successObj();
		break;
	case 'do_update':
		function revertUpdate() {
			$folder_backup = Files::get_folder_serverBackup();
			$file_update = Files::get_file_serverUpdate();
			
			if(file_exists($file_update))
				unlink($file_update);
			
			$h_folder = opendir($folder_backup);
			while($file = readdir($h_folder)) {
				if($file[0] != '.') {
					$oldLocation = $folder_backup .$file;
					$newLocation = DIR_BASE .$file;
					
					if(file_exists($newLocation)) {
						if(is_dir($newLocation)) {
							empty_folder($newLocation);
							rmdir($newLocation);
						}
						else
							unlink($newLocation);
					}
					rename($oldLocation, $newLocation);
				}
			}
			closedir($h_folder);
			
			rmdir($folder_backup);
		}
		
		$needsBackup = ['api/', 'backend/', 'frontend/', '.htaccess', 'CHANGELOG.md', 'index.php', 'index_nojs.php', 'LICENSE', 'README.md', 'version.txt'];
		$folder_backup = Files::get_folder_serverBackup();
		$file_update = Files::get_file_serverUpdate();
		
		if(!file_exists($file_update))
			Output::error('Could not find update. Has it been downloaded yet?');
		
		if(!file_exists($folder_backup))
			create_folder($folder_backup);
		
		//moving current files to backup location:
		foreach($needsBackup as $file) {
			$oldLocation = DIR_BASE .$file;
			$newLocation = $folder_backup .$file;
			
			if(!file_exists($oldLocation))
				continue;
			if(file_exists($newLocation)) {
				if(is_dir($newLocation)) {
					empty_folder($newLocation);
					rmdir($newLocation);
				}
				else
					unlink($newLocation);
			}
			
			if(!rename($oldLocation, $newLocation)) {
				revertUpdate();
				Output::error("Renaming $oldLocation to $newLocation failed. Reverting...");
			}
		}
		
		
		//unpacking update:
		$zip = new ZipArchive;
		if(!$zip->open($file_update)) {
			revertUpdate();
			Output::error("Could not open the the zipped update: $file_update. Reverting...");
		}
		if(!$zip->extractTo(DIR_BASE)) {
			revertUpdate();
			Output::error("Could not unzip update: $file_update. Reverting...");
		}
		$zip->close();
		
		//restore config file:
		if(!rename($folder_backup .Files::PATH_CONFIG, Files::FILE_CONFIG)) {
			revertUpdate();
			Output::error('Could not restore settings. Reverting...');
		}
		
		//cleaning up
		if(!empty_folder($folder_backup) || !rmdir($folder_backup))
			Output::error("Cleaning up backup failed. The update was successful. But please delete this folder and its contents manually: $folder_backup");
		
		Output::successObj();
		break;
	default:
		Output::error('Unexpected request');
}