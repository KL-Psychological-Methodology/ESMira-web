<?php
if(isset($_GET['csv']))
	header('Content-Type: text/csv;charset=UTF-8');
else
	header('Content-Type: application/json;charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');
require_once 'php/configs.php';
require_once 'php/permission_fu.php';
require_once 'php/global_json.php';
require_once 'php/string_fu.php';
require_once 'php/files.php';
require_once 'php/default_server_settings.php';

ignore_user_abort(true);
set_time_limit(0);

function getStudyId() {
	return rand(1000, 9999);
}
function getQuestionnaireId() {
	return rand(10000, 99999);
}

function empty_folder($path) { //TODO: check for errors!
	$h_folder = opendir($path);
	while($file = readdir($h_folder)) {
		if($file != '.' && $file != '..') {
			$filename = $path.$file;
			if(is_dir($filename)) {
				empty_folder($filename.'/');
				rmdir($filename);
			}
			else
				unlink($filename);
		}
	}
	closedir($h_folder);
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
	$file_responses = get_file_responses($study_id, $identifier);
	$file_index = get_file_responsesIndex($study_id, $identifier);
	
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
		
		if(count($index) != 0) {
			$file_responsesBackup = get_file_responsesBackup($study_id, $identifier);
			
			freeze_study($study_id);
			
			
			//move responses to a backup:
			if(rename($file_responses, $file_responsesBackup))
				chmod($file_responsesBackup, 0666);
			else {
				freeze_study($study_id, false);
				error("Could not rename $file_responses to $file_responsesBackup");
			}
			
			
			if(filesize($file_responsesBackup) > MAX_FILESIZE_FOR_CHANGES) { //the file is too big to be changed on the fly. So we just create a new file
				write_file($file_responses, '"'.implode('"'.CSV_DELIMITER.'"', $new_keys).'"');
				write_file($file_index, serialize($new_keys) .',');
				freeze_study($study_id, false);
				return;
			}
			
			
			//Preparing new header adding new keys to $old_key and preparing and $addedContent
			$addedContent = '';
			foreach($index as $value) {
				$addedContent .= CSV_DELIMITER .'""';
				$old_keys[] = $value;
			}
			
			
			
			//we read the backup and create a new responses file from that:
			
			$handle_newResponses = fopen($file_responses, 'w');
			$handle_backup = fopen($file_responsesBackup, 'r');
			
			if(!$handle_newResponses || !$handle_backup) {
				freeze_study($study_id, false);
				error("Could not open $file_responses or $file_responsesBackup");
			}
			flock($handle_newResponses, LOCK_EX);
			
			fgets($handle_backup); //loading first line - this is the old header. We dont need it
			
			
			if(feof($handle_backup)) { //there is no data. So we can just use the new headers
				fputs($handle_newResponses, '"'.implode('"'.CSV_DELIMITER.'"', $new_keys).'"');
				write_file($file_index, serialize($new_keys) .',');
				unlink($file_responsesBackup); //there is no point in keeping this backup
			}
			else {
				fputs($handle_newResponses, '"'.implode('"'.CSV_DELIMITER.'"', $old_keys).'"');
				
				while(($line = fgets($handle_backup)) !== false) {
					fputs($handle_newResponses, "\n".rtrim($line, "\n").$addedContent);
				}
				
				write_file($file_index, serialize($old_keys) .',');
			}
			
			
			fflush($handle_newResponses);
			flock($handle_newResponses, LOCK_UN);
			fclose($handle_newResponses);
			fclose($handle_backup);
			freeze_study($study_id, false);
		}
	}
	else {
		write_file($file_responses, '"'.implode('"'.CSV_DELIMITER.'"', $new_keys).'"');
		write_file($file_index, serialize($new_keys) .',');
	}
}
function write_statistics($study) {
	$study_id = $study->id;
	if($study->publicStatistics->observedVariables !== new stdClass()) { //check if empty
		$folder_statistics = get_folder_statistics($study_id);
		$file_statisticsMetadata = get_file_statisticsMetadata($study_id);
		$file_statisticsJson = get_file_statisticsJson($study_id);
		
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
			foreach($keyBox as $i => $observedEntry) {
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
			error('Questionnaire title is empty!');
		
		$questionnaire_title = $questionnaire->title; //only used for error feedback
		$keys_questionnaire = KEYS_QUESTIONNAIRE_BASE_RESPONSES; //Note: php always creates copies, which is what we need right now
		
		//make sure input and sumScore names are unique:
		if(isset($questionnaire->pages)) {
			foreach($questionnaire->pages as $page) {
				foreach($page->inputs as $input) {
					$responseType = isset($input->responseType) ? $input->responseType : 'text_input';
					
					switch($responseType) {
						case 'text':
							continue 2;
					}
					
					$name = $input->name;
					
					if(!strlen($name))
						error('Input name is empty!');
					else if(!check_input($name))
						error("No special characters are allowed in Variable-Names. \n'$name' detected in questionnaire: $questionnaire_title");
					else if(isset($key_check_array[$name]))
						error("Variable-Name exists more than once: '$name'. First detected in questionnaire: '".$key_check_array[$input->name]."'. Detected again in questionnaire: '$questionnaire_title'");
					else if(in_array($name, KEYS_EVENT_RESPONSES) || in_array($name, KEYS_QUESTIONNAIRE_BASE_RESPONSES))
						error("Protected Variable-Name: $name \nPlease choose another Variable-Name.\nDetected in questionnaire: $questionnaire_title");
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

function write_serverSettings($serverNames, $langCodes) {
	$serverSettings = DEFAULT_SERVER_SETTINGS;
	$serverSettings['serverName'] = $serverNames;
	$serverSettings['langCodes'] = $langCodes;
	
	return write_file(FILE_SERVER_SETTINGS, '<?php const SERVER_SETTINGS='.var_export($serverSettings, true).';?>');
}
function check_userExists($user) {
	$h = fopen(FILE_LOGINS, 'r');
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

function update_loginsFile($user, $new_user=null, $new_pass=null) {
	$export = '';
	$h = fopen(FILE_LOGINS, 'r');
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
		write_file(FILE_LOGINS, $export);
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
		error('Writing the file \'' . $file . '\' failed');
		return false;
	}
	else {
		chmod($file, 0666);
		return true;
	}
}


function check_error_filename($filename) {
	return preg_match('/^_?\d+~?[^\/\.\?~]*$/', $filename);
}

if(!isset($_GET['type']))
	error('No data');
$type = $_GET['type'];

//is not logged in
switch($type) {
	case 'init_esmira':
		if(file_exists(FOLDER_DATA))
			error('Disabled');
		else {
			create_folder(FOLDER_DATA);
			
			$user = $_POST['new_user'];
			$pass = $_POST['pass'];
			$serverName = ['_' => $_POST['server_name']];
			if(!file_put_contents(FILE_LOGINS, $user .':' .get_hashed_pass($pass) ."\n", FILE_APPEND))
				error('Login data could not be saved');
			
			$permissions = [$user => ['admin' => true]];
			write_file(FILE_PERMISSIONS, serialize($permissions));
			
			write_serverSettings($serverName, []);
			
			write_file(FOLDER_DATA .FILENAME_HTACCESS, sprintf(HTACCESS_MAIN_TEMPLATE, realpath(FILE_LOGINS)));
			
			
			create_folder(FOLDER_ERRORS);
			create_folder(FOLDER_LEGAL);
			create_folder(FOLDER_TOKEN);
			
			create_folder(FOLDER_STUDIES);
			write_file(FILE_STUDY_INDEX, serialize([]));
			
			set_loggedIn($user);
			success();
		}
		
		error('Unknown error');
		break;
	case 'login':
		if(!isset($_POST['user']) || !isset($_POST['pass']))
			error('Missing data');
		$user = $_POST['user'];
		
		$blockTIme = 0;
		if(!check_login($user, $_POST['pass'], $blockTIme)) {
			if($blockTIme != 0)
				error("Please wait for $blockTIme seconds.");
			else
				return error('Wrong password');
		}
		
		if(isset($_POST['rememberMe']))
			create_token($user);
		
		set_loggedIn($user);
		goto get_permissions;
		
		break;
	case 'logout':
		set_loggedOut();
		success();
		break;
	case 'get_permissions':
		get_permissions:
		if(!file_exists(FOLDER_DATA))
			success('{"init_esmira":true}');
		else if(!is_loggedIn())
			success('{"isLoggedIn":false}');
		else {
			function list_additionalPermissions($is_admin, &$userPermissions) {
				$new_messages = [];
				$needsBackup = [];
				$lastActivities = [];
				$count = 0;
				$h_folder = opendir(FOLDER_STUDIES);
				$writePermissions = !$is_admin && isset($userPermissions['write']) ? $userPermissions['write'] : [];
				$msgPermissions = !$is_admin && isset($userPermissions['msg']) ? $userPermissions['write'] : [];
				while($study_id = readdir($h_folder)) {
					if($study_id[0] === '.' || $study_id === FILENAME_STUDY_INDEX)
						continue;
					
					//new messages:
					if($is_admin || in_array($study_id, $msgPermissions)) {
						$studyDir = get_folder_messages_unread($study_id);
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
						$metadata_path = get_file_studyMetadata($study_id);
						if(file_exists($metadata_path)) {
							$metadata = unserialize(file_get_contents($metadata_path));
							if(isset($metadata['published']) && $metadata['published'] && (!isset($metadata['lastBackup']) || get_milliseconds() - $metadata['lastBackup'] > BACKUP_INTERVAL_DAYS * 24*60*60*1000)) {
								array_push($needsBackup, (int) $study_id);
							}
						}
					}
					
					//last activity:
					$events_path = get_file_responses($study_id, FILENAME_EVENTS);
					if(file_exists($events_path))
						array_push($lastActivities, [(int) $study_id, filemtime($events_path)]);
				}
				closedir($h_folder);
				$new_messages['count'] = $count;
				
				
				$userPermissions['new_messages'] = $new_messages;
				$userPermissions['needsBackup_list'] = $needsBackup;
				$userPermissions['lastActivities'] = $lastActivities;
			}
			
			if(is_admin()) {
				$obj = ['is_admin' => true];
				$has_errors = false;
				$msg = [];
				$h_folder = opendir(FOLDER_ERRORS);
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
				$obj['permissions'] = get_permissions();
				list_additionalPermissions(false, $obj);
			}
			$obj['username'] = get_user();
			$obj['isLoggedIn'] = true;
			$obj['loginTime'] = time();
			success(json_encode($obj));
		}
		break;
}

if(!is_loggedIn())
	error('No permission');


$study_id = isset($_POST['study_id']) ? (int) $_POST['study_id'] : (isset($_GET['study_id']) ? (int) $_GET['study_id'] : 0);

$is_admin = is_admin();

//is logged in or read permission:
switch($type) {
	case 'change_password':
		if(!isset($_POST['pass']))
			error('Unexpected data');
		
		$pass = $_POST['pass'];
		
		if($is_admin && isset($_POST['user']))
			$user = $_POST['user'];
		else
			$user = get_user();
		
		if(strlen($pass) < 12)
			error('The password needs to have at least 12 characters.');
		
		if(update_loginsFile($user, null, get_hashed_pass($pass)))
			success();
		else
			error('User does not exist.');
		break;
	case 'change_username':
		if(!isset($_POST['new_user']))
			error('Unexpected data');
		
		if($is_admin && isset($_POST['user']))
			$user = $_POST['user'];
		else
			$user = get_user();
		
		$new_user = $_POST['new_user'];
		
		if(check_userExists($new_user))
			error("Username '$new_user' already exists");
		
		$permissions = unserialize(file_get_contents(FILE_PERMISSIONS));
		if($permissions) {
			if(isset($permissions[$user])) {
				$p = $permissions[$new_user] = $permissions[$user];
				unset($permissions[$user]);
				
				if(isset($p['admin'])) {
					$h_folder = opendir(FOLDER_STUDIES);
					while($file = readdir($h_folder)) {
						if($file[0] != '.') {
							create_readPermission_htaccessFile($file, $permissions);
						}
					}
					closedir($h_folder);
				}
				else if(isset($p['read'])) {
					foreach($p['read'] as $study_id) {
						create_readPermission_htaccessFile($study_id, $permissions);
					}
				}
			}
			
			write_file(FILE_PERMISSIONS, serialize($permissions));
		}
		update_loginsFile($user, $new_user);
		
		$folder_token = get_folder_token($user);
		if(file_exists($folder_token))
			rename($folder_token, get_folder_token($new_user));
		
		if(get_user() == $user) {
			$_SESSION['user'] = $new_user;
			if(isset($_COOKIE['user']))
				create_cookie('user', $_COOKIE['user'] = $new_user, time()+31536000);
		}
		
		success();
		break;
	case 'get_tokenList':
		get_tokenList:
		$user = get_user();
		$folder_token = get_folder_token($user);
		$currentToken = get_currentToken();
		
		$obj = [];
		if(file_exists($folder_token)) {
			$h_folder = opendir($folder_token);
			while($file = readdir($h_folder)) {
				if($file[0] != '.')
					array_push($obj, ['hash' => $file, 'last_used' => filemtime($folder_token.$file), 'current' => ($file === $currentToken)]);
			}
			closedir($h_folder);
		}
		
		success(json_encode($obj));
		break;
	case 'get_loginHistory':
		$user = get_user();
		
		$file_history1 = get_file_tokenHistory($user, 1);
		$file_history2 = get_file_tokenHistory($user, 2);
		$exists1 = file_exists($file_history1);
		$exists2 = file_exists($file_history2);
		
		header('Content-Length: ' .(($exists1 ? filesize($file_history1) : 0) + ($exists2 ? filesize($file_history2) : 0)));
		echo 'date'.CSV_DELIMITER.'login'.CSV_DELIMITER.'ip'.CSV_DELIMITER.'userAgent';
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
		break;
	case 'remove_token':
		$user = get_user();
		$token_id = $_POST['token_id'];
		remove_token($user, $token_id);
		
		goto get_tokenList;
		break;
	case 'get_new_id':
		$forQuestionnaire = $_GET['for'] === 'questionnaire';
		$filtered = $forQuestionnaire ? json_decode(file_get_contents('php://input')) : [];
		
		$study_index = file_exists(FILE_STUDY_INDEX) ? unserialize(file_get_contents(FILE_STUDY_INDEX)) : [];
		
		$i = 0;
		do {
			$id = $forQuestionnaire ? getQuestionnaireId() : getStudyId();
			
			if(++$i > 1000)
				error('Could not find an unused id...');
		} while(file_exists(get_folder_study($id)) || isset($study_index["~$id"]) || isset($filtered[$id]));
		success($id);
		break;
	case 'list_data':
		if(!$is_admin && !has_permission($study_id, 'read'))
			error('No permission');
		
		$l_folder = opendir(get_folder_responses($study_id));
		
		$msg = [];
		$events_file = FILENAME_EVENTS.'.csv';
		$webAccess_file = FILENAME_WEB_ACCESS.'.csv';
		while($file = readdir($l_folder)) {
			if($file[0] != '.' && $file != $events_file && $file != $webAccess_file) {
				$msg[] = '"' .substr($file, 0, -4) .'"';
			}
		}
		success('[' .implode(',', $msg) .']');
		break;
}

//has msg permission:
if($study_id != 0 && ($is_admin || has_permission($study_id, 'msg'))) {
	switch($type) {
		case 'send_message':
			function send_message($study_id, $from, $user, $content) {
			    if(!strlen($user))
			        return false;
				$msg = [
					'from' => $from,
					'content' => $content,
					'sent' => get_milliseconds(),
					'pending' => true,
					'delivered' => 0
				];
				
				$file = get_file_message_pending($study_id, $user);

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
				error('Input is faulty');
			
			$from = get_user();
			$content = $json->content;
			$toAll = $json->toAll;
			
			
			if(strlen($content) < 2)
				error("Message is too short");
			
			if($json->toAll) {
				$appVersion = $json->appVersion;
				$appType = isset($json->appType) ? $json->appType : false;
				$checkUserdata = $appVersion || $appType;
				
				
				$usernames_folder = get_folder_userData($study_id);
				$count = 0;
				if(file_exists($usernames_folder)) {
					$h_folder = opendir($usernames_folder);
					while($file = readdir($h_folder)) {
						if($file[0] != '.') {
							$user = get_urlFriendly($file);
							if($checkUserdata) {
								$userdata = unserialize(file_get_contents($usernames_folder.$file));
								if(($appVersion && $userdata['appVersion'] != $appVersion) || ($appType &&$userdata['appType'] != $appType))
									continue;
							}
							++$count;
							if(!send_message($study_id, $from, $user, $content))
								error("Could not save message for $user. $count messages have already been sent. Aborting now...");
						}
					}
					closedir($h_folder);
				}
			}
			else {
				$user = $json->user;
				if(!check_input($user))
					error('Recipient is faulty');
				
				if(!send_message($study_id, $from, $user, $content))
					error("Could not save message");
			}
			goto messages_setRead;
		case 'delete_message':
			$user = $_POST['user'];
			$sent = $_POST['sent'];
			$msgs_pending_folder = get_folder_messages_pending($study_id);
			
			$file_pending = get_file_message_pending($study_id, $user);
			if(!file_exists($file_pending))
				error('Message does not exist');
			
			$changeMessages = unserialize(file_get_contents($file_pending));
			
			foreach($changeMessages as $index => $cMsg) {
				if($cMsg['sent'] == $sent) {
					array_splice($changeMessages, $index, 1);
					break;
				}
			}
			
			if(count($changeMessages) === 0) {
				if(unlink($file_pending))
					success("[]");
				else
					error("Could not delete $file_pending");
			}
			else if(write_file($file_pending, serialize($changeMessages)))
				success(json_encode($changeMessages));
			else
				error("Could not save message");
			break;
		case 'list_userWithMessages':
			function indexFolder(&$index, &$msgs, $folder, $attr = false) {
				if(file_exists($folder)) {
					$h_folder = opendir($folder);
					while($file = readdir($h_folder)) {
						if($file[0] != '.') {
							$username = get_urlFriendly($file);
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
			
			$msgs_archive_folder = get_folder_messages_archive($study_id);
			$msgs_pending_folder = get_folder_messages_pending($study_id);
			$msgs_unread_folder = get_folder_messages_unread($study_id);
			
			$changeMessages = [];
			$index = [];
			if(file_exists($msgs_unread_folder))
				indexFolder($index, $changeMessages, $msgs_unread_folder, 'unread');
			if(file_exists($msgs_pending_folder))
				indexFolder($index, $changeMessages, $msgs_pending_folder, 'pending');
			if(file_exists($msgs_archive_folder))
				indexFolder($index, $changeMessages, $msgs_archive_folder);
			
			
			success(json_encode($changeMessages));
			break;
		case 'list_messages':
			$user = $_GET['user'];
			if(!check_input($user))
				error('Username is faulty');
			
			if(!strlen($user)) {
				$changeMessages = [
					'archive' => [],
					'pending' => [],
					'unread' => []
				];
			}
			else {
				$file_archive = get_file_message_archive($study_id, $user);
				$file_pending = get_file_message_pending($study_id, $user);
				$file_unread = get_file_message_unread($study_id, $user);
				
				$changeMessages = [
					'archive' => file_exists($file_archive) ? unserialize(file_get_contents($file_archive)) : [],
					'pending' => file_exists($file_pending) ? unserialize(file_get_contents($file_pending)) : [],
					'unread' => file_exists($file_unread) ? unserialize(file_get_contents($file_unread)) : []
				];
			}
			
			success(json_encode($changeMessages));
			break;
		case 'messages_setRead':
			messages_setRead:
			if(!isset($json))
				$json = json_decode(file_get_contents('php://input'));
			
			$changeMessages = $json->timestamps;
			$user = $json->user;
			
			$file_unread = get_file_message_unread($study_id, $user);
			if(!file_exists($file_unread))
				success(1);
			
			$handle_unread = fopen($file_unread, 'r+');
			if(!$handle_unread)
				error("Could not open $file_unread");
			flock($handle_unread, LOCK_EX);
			$messages_unread = unserialize(fread($handle_unread, filesize($file_unread)));
			
			
			
			$file_archive = get_file_message_archive($study_id, $user);
			if(file_exists($file_archive)) {
				$handle_archive = fopen($file_archive, 'r+');
				if(!$handle_archive) {
					flock($handle_unread, LOCK_UN);
					fclose($handle_unread);
					error("Could not open $file_archive");
				}
				$messages_archive = unserialize(fread($handle_archive, filesize($file_archive)));
				
				fseek($handle_archive, 0);
				if(!ftruncate($handle_archive, 0)) {
					flock($handle_unread, LOCK_UN);
					fclose($handle_unread);
					fclose($handle_archive);
					error("Could not empty $file_archive");
				}
			}
			else {
				$handle_archive = fopen($file_archive, 'w');
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
				error($error);
			else
				success(1);
			
			break;
		case 'list_usernames':
			$usernames_folder = get_folder_userData($study_id);
			$usernames = [];
			if(file_exists($usernames_folder)) {
				$h_folder = opendir($usernames_folder);
				while($file = readdir($h_folder)) {
					if($file[0] != '.') {
						$usernames[] = get_urlFriendly($file);
					}
				}
				closedir($h_folder);
			}
			success(json_encode($usernames));
			break;
	}
}

//has write permission:
if($study_id != 0 && ($is_admin || has_permission($study_id, 'write'))) {
	switch($type) {
		case 'empty_data':
			$responses_folder = get_folder_responses($study_id);
			if(file_exists($responses_folder))
				empty_folder($responses_folder);
			else
				return error("$responses_folder does not exist");
			
			
			//delete statistics
			$statistics_folder = get_folder_statistics($study_id);
			if(file_exists($statistics_folder))
				empty_folder($statistics_folder);
			else
				return error("$statistics_folder does not exist");
			
			//recreate study
			$study_file = get_file_studyConfig($study_id);
			if(file_exists($study_file))
				$study_json = file_get_contents($study_file);
			else
				return error("$study_file does not exist");
			
			
			
			if(!($study = json_decode($study_json)))
				return error('Unexpected data');
			
			$keys = checkUnique_and_collectKeys($study);
			foreach($study->questionnaires as $i => $q) {
				write_indexAndResponses_files($study, $q->internalId, $keys[$i]);
			}
			write_statistics($study);
			
			break;
		case 'check_changed':
			$sentChanged = (int) $_GET['lastChanged'];
			$file_config = get_file_studyConfig($study_id);
			
			if(!file_exists($file_config))
				error('Study does not exist');
			
			
			$realChanged = filemtime($file_config);
			if($realChanged > $sentChanged) {
				$study = file_get_contents($file_config);
				success("{\"lastChanged\": $realChanged, \"json\": $study}");
			}
			else
				success("{\"lastChanged\": $realChanged}");
			
			break;
		case 'load_langs':
			$folder_langs = get_folder_langs($study_id);
			$langObj = [];
			if(file_exists($folder_langs)) {
				$h_folder = opendir($folder_langs);
				while($file = readdir($h_folder)) {
					if($file[0] != '.') {
						$s = file_get_contents($folder_langs .$file);
						$langObj[] = '"' .explode('.', $file)[0] ."\": $s";
					}
				}
				closedir($h_folder);
			}
			success('{' .implode(',', $langObj) .'}');
			break;
		case 'save_study':
			$studyCollection_json = file_get_contents('php://input');
			
			if(!($studyCollection = json_decode($studyCollection_json)))
				error('Unexpected data');
			
			if(!isset($studyCollection->_))
				error('No default study');
			
			$study = $studyCollection->_;
			
			if(!isset($study->id) || $study->id != $study_id)
				error("Problem with study id! $study_id !=" .$study->id);
			
			$file_config = get_file_studyConfig($study_id);
			
			if(isset($_GET['lastChanged']) && file_exists($file_config) && filemtime($file_config) > $_GET['lastChanged'])
				error('The study configuration was changed (by another user?) since you last loaded it. You can not save your changes. Please reload the page.');
			
			
			$study_index = file_exists(FILE_STUDY_INDEX) ? unserialize(file_get_contents(FILE_STUDY_INDEX)) : [];
			
			//*****
			//check and prepare questionnaires:
			//*****
			
			$keys_questionnaire_array = checkUnique_and_collectKeys($study);
			
			
            //*****
            //create folders
            //*****
			
            $folder_study = get_folder_study($study_id);
            $folder_langConfigs = get_folder_langs($study_id);
            $folder_study_responses = get_folder_responses($study_id);
            $folder_study_messages = get_folder_messages($study_id);
            $folder_study_messages_user = get_folder_messages_archive($study_id);
            $folder_study_messages_outgoing = get_folder_messages_pending($study_id);
            $folder_study_messages_unread = get_folder_messages_unread($study_id);
            $folder_study_responsesIndex = get_folder_responsesIndex($study_id);
            $folder_study_statistics = get_folder_statistics($study_id);
            $folder_study_token = get_folder_userData($study_id);
            $file_htaccess = $folder_study_responses .FILENAME_HTACCESS;
			
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
			
            if(!file_exists($file_htaccess))
                create_readPermission_htaccessFile($study_id);
			
			
            //*****
            //save questionnaire index (has to happen after folders are created)
            //*****
			
            foreach($study->questionnaires as $i => $q) {
                write_indexAndResponses_files($study, $q->internalId, $keys_questionnaire_array[$i]);
            }
			
			
			//*****
			//Creating observable variables and statistics
			//*****
			
			function get_conditionString($key, $storageType, $timeInterval, $conditions) {
				$a = [];
				foreach($conditions as $c) {
					array_push($a, $c->key .$c->operator .$c->value);
				}
				sort($a);
				return $key .$storageType .$timeInterval .implode('', $a);
			}
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
					$conditionType = isset($axisData->conditionType) ? $axisData->conditionType : CONDITION_TYPE_ALL;
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
					$dataType = isset($defaultChart->dataType) ? number_format($defaultChart->dataType) : STATISTICS_DATATYPES_DAILY;
					switch($dataType) {
						case STATISTICS_DATATYPES_SUM:
						case STATISTICS_DATATYPES_DAILY:
							$timeInterval = ONE_DAY;
							$storageType = STATISTICS_STORAGE_TYPE_TIMED;
							break;
						case STATISTICS_DATATYPES_XY:
						default:
							$timeInterval = SMALLEST_TIMED_DISTANCE;
							$storageType = STATISTICS_STORAGE_TYPE_TIMED;
							break;
						case STATISTICS_DATATYPES_FREQ_DISTR:
							$timeInterval = 0;
							$storageType = STATISTICS_STORAGE_TYPE_FREQ_DISTR;
							break;
					}
					
					
					foreach($defaultChart->axisContainer as $axis_i => &$defaultAxisContainer) {
						check_axis($defaultAxisContainer->yAxis, $index, $observedVariables, $storageType, $timeInterval);
						
						if($dataType == STATISTICS_DATATYPES_XY)
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
							if($dataType == STATISTICS_DATATYPES_XY)
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
			if($is_admin || has_permission($study_id, 'publish')) {
				$removeCount = remove_study_from_index($study_index, $study_id);
				
				if(isset($study->published) && $study->published) {
					//entries for accessKeys:
					if(isset($study->accessKeys) && count($study->accessKeys)) {
						foreach($study->accessKeys as $key => $value) {
							$value = strtolower($value);
							foreach($studyCollection as &$langStudy) {
								$langStudy->accessKeys[$key] = $value;
							}
							
							if(!check_input($value))
								error("No special characters are allowed in access keys.\n'$value'");
							else if(!preg_match("/^([a-zA-Z][a-zA-Z0-9]*)$/", $value))
								error("Access keys need to start with a character.");
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
						update_serverStatistics(function(&$statistics) {
							$statistics->total->studies += 1;
						});
					}
				}
				else if($removeCount) {
					update_serverStatistics(function(&$statistics) {
						$statistics->total->studies -= 1;
					});
				}
				write_file(FILE_STUDY_INDEX, serialize($study_index));
			}
			else {
				$old_study = json_decode(file_get_contents($file_config));
				
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
			
//			exit(json_encode($studyCollection));
			$studies_json = [];
			foreach($studyCollection as $code => $s) {
				$study_json = json_encode($s);
				write_file($code === '_' ? $file_config : get_file_langConfig($study_id, $code), $study_json);
				$studies_json[] = "\"$code\":$study_json";
			}
			
			
			//
			//create web_access and events file
			//
			write_indexAndResponses_files($study, FILENAME_EVENTS, KEYS_EVENT_RESPONSES);
			write_indexAndResponses_files($study, FILENAME_WEB_ACCESS, KEYS_WEB_ACCESS);


			//
			//save index-files
			//
			$metadata = get_newMetadata($study);
			write_file(get_file_studyMetadata($study_id), serialize($metadata));
			$sentChanged = time();
			success("{\"lastChanged\":$sentChanged,\"json\":{" .implode(',', $studies_json) ."}}");
			break;
		case 'mark_study_as_updated'://this will mark the study as updated for already existing participants
			$file = get_file_studyConfig($study_id);
			if(!($study = json_decode(file_get_contents($file))))
				error('Unexpected data');
			
			$study->version = isset($study->version) ? $study->version + 1 : 1;
			$study->subVersion = 0;
			$study->new_changes = false;
			
			write_file($file, json_encode($study));
			
			$metadata = get_newMetadata($study);
			write_file(get_file_studyMetadata($study_id), serialize($metadata));
			
			$sentChanged = time();
			success("{\"lastChanged\":$sentChanged}");
			break;
		case 'backup_study':
			$study = json_decode(file_get_contents(get_file_studyConfig($study_id)));
			
			$metadata_path = get_file_studyMetadata($study_id);
			if(!file_exists($metadata_path))
				error('Metadata file does not exist. Save the study to create it.');
			
			$metadata = unserialize(file_get_contents($metadata_path));
			
			function backup($study_id, $identifier) {
				$file_name = get_file_responses($study_id, $identifier);
				$file_backupName = get_file_responsesBackup($study_id, $identifier);
				
				if(!copy($file_name, $file_backupName))
					error("Copying $file_name to $file_backupName failed");
			}
			foreach($study->questionnaires as $questionnaire) {
				backup($study_id, $questionnaire->internalId);
			}
			
			backup($study_id, FILENAME_EVENTS);
			backup($study_id, FILENAME_WEB_ACCESS);
			$metadata['lastBackup'] = get_milliseconds();
			write_file($metadata_path, serialize($metadata));
			success(1);
			
			break;
		case 'is_frozen':
			success(study_is_locked($study_id) ? 'true' : 'false');
			break;
		case 'freeze_study':
			freeze_study($study_id, isset($_GET['frozen']));
			success(study_is_locked($study_id) ? 'true' : 'false');
			break;
	}
}

if(!$is_admin)
	error('No permission');

//is admin:
switch($type) {
	case 'get_serverSettings':
		require_once FILE_SERVER_SETTINGS;
		
		$serverSettings = SERVER_SETTINGS;
		$serverSettings['impressum'] = [];
		$serverSettings['privacyPolicy'] = [];
		
		$langCodes = SERVER_SETTINGS['langCodes'];
		array_push($langCodes, '_');
		foreach($langCodes as $code) {
			$file_impressum = get_file_langImpressum($code);
			if(file_exists($file_impressum))
				$serverSettings['impressum'][$code] = file_get_contents($file_impressum);
			
			$file_privacyPolicy = get_file_langPrivacyPolicy($code);
			if(file_exists($file_privacyPolicy))
				$serverSettings['privacyPolicy'][$code] = file_get_contents($file_privacyPolicy);
		}
		success(json_encode($serverSettings));
		break;
	case 'save_serverSettings':
        $settingsCollection_json = file_get_contents('php://input');

        if(!($settingsCollection = json_decode($settingsCollection_json)))
            error('Unexpected data');

        if(!isset($settingsCollection->_))
            error('No default settings');

        require_once FILE_SERVER_SETTINGS;
        $old_langCodes = SERVER_SETTINGS['langCodes'];
        
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
                error('The server name needs to consist of 3 and 30 characters');
            else if(!check_input($serverName))
                error('The server name has forbidden characters');
            else
				$serverNames[$code] = $serverName;
            
            $file_impressum = get_file_langImpressum($code);
            if(strlen($impressum))
				write_file($file_impressum, $impressum);
            else if(file_exists($file_impressum))
                unlink($file_impressum);
	
			$file_privacyPolicy = get_file_langPrivacyPolicy($code);
            if(strlen($privacyPolicy))
				write_file($file_privacyPolicy, $privacyPolicy);
            else if(file_exists($file_privacyPolicy))
                unlink($file_privacyPolicy);
        }
        
        //if a language has been removed, we need to remove its files too:
        foreach($old_langCodes as $code) {
			$file_impressum = get_file_langImpressum($code);
			if(file_exists($file_impressum))
				unlink($file_impressum);
			
			$file_privacyPolicy = get_file_langPrivacyPolicy($code);
			if(file_exists($file_privacyPolicy))
				unlink($file_privacyPolicy);
		}
		
		write_serverSettings($serverNames, $langCodes);
  		
        success(1);
		break;
	case 'error_add_note':
		if(!isset($_POST['error_id']) || !isset($_POST['note']) || !check_error_filename($_POST['error_id']))
			error('Faulty data');
		$filename = $_POST['error_id'];
		$newfilename = explode('~', $filename)[0].'~'.$_POST['note'];
		
		if(!check_error_filename($newfilename))
			error('Name has forbidden characters!');
		
		if(file_exists(FOLDER_ERRORS.$filename.ERROR_FILE_EXTENSION) && rename(FOLDER_ERRORS.$filename.ERROR_FILE_EXTENSION, FOLDER_ERRORS.$newfilename.ERROR_FILE_EXTENSION))
			success();
		else
			error('Could not rename ' .FOLDER_ERRORS.$filename.ERROR_FILE_EXTENSION);
		break;
	case 'mark_error_seen':
		if(!isset($_POST['error_id']) || !check_error_filename($_POST['error_id']))
			error('Faulty data');
		$filename = $_POST['error_id'];
		
		if(file_exists(FOLDER_ERRORS.$filename.ERROR_FILE_EXTENSION) && rename(FOLDER_ERRORS.$filename.ERROR_FILE_EXTENSION, FOLDER_ERRORS.'_'.$filename.ERROR_FILE_EXTENSION))
			success();
		else
			error('Could not rename ' .FOLDER_ERRORS.$filename);
		break;
	case 'delete_error':
		if(!isset($_POST['error_id']) || !check_error_filename($_POST['error_id']))
			error('Faulty data');
		$filename = $_POST['error_id'];
		
		if(file_exists(FOLDER_ERRORS.$filename.ERROR_FILE_EXTENSION) && unlink(FOLDER_ERRORS.$filename.ERROR_FILE_EXTENSION))
			success();
		else
			error('Could not remove ' .FOLDER_ERRORS.$filename.ERROR_FILE_EXTENSION);
		
		break;
	case 'list_errors':
		$msg = [];
		$h_folder = opendir(FOLDER_ERRORS);
		while($file = readdir($h_folder)) {
			if($file[0] != '.') {
				$msg[] = '"' .explode('.', $file)[0] .'"';
			}
		}
		closedir($h_folder);
		success('['.implode(',', $msg).']');
		break;
	case 'delete_study':
		if($study_id == 0)
			error('Missing data');
		
		//remove from study-index
		$study_index = file_exists(FILE_STUDY_INDEX) ? unserialize(file_get_contents(FILE_STUDY_INDEX)) : [];
		remove_study_from_index($study_index, $study_id);
		write_file(FILE_STUDY_INDEX, serialize($study_index));
		
		
		//remove study data
		$folder_study = get_folder_study($study_id);
		if(file_exists($folder_study)) {
			empty_folder($folder_study);
			if(!rmdir($folder_study))
				error("Could not remove $folder_study");
		}
		else
			error("$folder_study does not exist!");
		
		
		//remove from permissions
		$permissions = unserialize(file_get_contents(FILE_PERMISSIONS));
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
			write_file(FILE_PERMISSIONS, serialize($permissions));
		}
		
		success($study_id);
		break;
	case 'list_users':
		$permissions = unserialize(file_get_contents(FILE_PERMISSIONS));
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
		$h = fopen(FILE_LOGINS, 'r');
		
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
		success(json_encode($userList));
		break;
	case 'create_user':
		if(!isset($_POST['new_user']) || !isset($_POST['pass']) || strlen($_POST['pass']) <= 3)
			error('Unexpected data');
		
		$user = $_POST['new_user'];
		if(check_userExists($user))
			error("Username '$user' already exists");
		
		$pass = get_hashed_pass($_POST['pass']);
		
		
		if(!file_put_contents(FILE_LOGINS, $user .':' .$pass ."\n", FILE_APPEND))
			error('Login data could not be saved');
		else
			success("{\"username\":\"$user\"}");
		break;
	case 'delete_user':
		if(!isset($_POST['user']))
			error('Unexpected data');
		
		$user = $_POST['user'];
		
		//remove permissions:
		$permissions = unserialize(file_get_contents(FILE_PERMISSIONS));
		if($permissions) {
			if(isset($permissions[$user])) {
				$userPerms = $permissions[$user];
				unset($permissions[$user]);//needs to happen before create_readPermission_htaccessFile()
				
				if(isset($userPerms['admin']) && $userPerms['admin']) {
					$h_folder = opendir(FOLDER_STUDIES);
					while($file = readdir($h_folder)) {
						if($file[0] != '.') {
							create_readPermission_htaccessFile($file, $permissions);
						}
					}
					closedir($h_folder);
				}
				else if(isset($userPerms['read'])) {
					$a = $userPerms['read'];
					foreach($a as $study_id) {
						create_readPermission_htaccessFile($study_id, $permissions);
					}
				}
				
			}
			
			write_file(FILE_PERMISSIONS, serialize($permissions));
		}
		
		update_loginsFile($user);
		
		success('"Success"');
		break;
	case 'toggle_admin':
		if(!isset($_POST['user']))
			error('Missing data');
		else if(get_user() === $_POST['user'])
			error('You can not remove your own admin permissions');
		
		$user = $_POST['user'];
		$admin = isset($_POST['admin']);
		
		$permissions = unserialize(file_get_contents(FILE_PERMISSIONS));
		if(!$permissions)
			$permissions = [];
		
		if(!isset($permissions[$user]))
			$permissions[$user] = ['admin' => $admin];
		else
			$permissions[$user]['admin'] = $admin;
		
		
		write_file(FILE_PERMISSIONS, serialize($permissions));
		
		$h_folder = opendir(FOLDER_STUDIES);
		while($file = readdir($h_folder)) {
			if($file[0] != '.') {
				create_readPermission_htaccessFile($file, $permissions);
			}
		}
		closedir($h_folder);
		
		success(1);
		break;
	case 'add_userPermission':
		if(!isset($_POST['user']) || !isset($_POST['permission']) || $study_id == 0)
			error('Missing data');
		
		$user = $_POST['user'];
		$permCode = $_POST['permission'];
		
		$permissions = unserialize(file_get_contents(FILE_PERMISSIONS));
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
				error('Faulty data');
		}
		
		write_file(FILE_PERMISSIONS, serialize($permissions));
		if($permCode === 'read') //has to happen after FILE_PERMISSIONS has been written
			create_readPermission_htaccessFile($study_id);
		success(1);
		break;
	case 'delete_userPermission':
		if(!isset($_POST['user']) || !isset($_POST['permission']) || $study_id == 0)
			error('Missing data');
		
		$user = $_POST['user'];
		$permCode = $_POST['permission'];
		
		
		$permissions = unserialize(file_get_contents(FILE_PERMISSIONS));
		if(!$permissions)
			error('No permissions to remove');
		else if(!isset($permissions[$user]))
			error('User has no permissions');
		
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
				error('Faulty data');
		}
		
		
		write_file(FILE_PERMISSIONS, serialize($permissions));
		if($permCode == 'read') //has to happen after FILE_PERMISSIONS has been written
			create_readPermission_htaccessFile($study_id);
		
		success(1);
		break;
	default:
		error('Unexpected request');
}

?>