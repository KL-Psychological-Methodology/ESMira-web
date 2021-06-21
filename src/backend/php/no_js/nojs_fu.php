<?php
require_once 'php/files.php';
require_once 'php/basic_fu.php';

function questionnaire_isActive($questionnaire) {
	return (!isset($questionnaire->publishedWeb) || $questionnaire->publishedWeb)
		&& (!isset($questionnaire->durationStart) || !$questionnaire->durationStart || time() >= $questionnaire->durationStart)
		&& (!isset($questionnaire->durationEnd) || !$questionnaire->durationEnd || time() <= $questionnaire->durationEnd)
		&& isset($questionnaire->pages) && sizeof($questionnaire->pages);
}
function remove_postHeader() {
	//remove POST-data from history and reload page:
	header('Location:' . (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	exit();
}

function get_questionnaire($study, $qId) {
	foreach($study->questionnaires as $questionnaire) {
		if($questionnaire->internalId === $qId) {
			return $questionnaire;
		}
	}
	return null;
}
function load_study($study_id) {
	$json_string = file_get_contents(get_file_studyConfig($study_id));
	if(!$json_string)
		return null;
	return json_decode($json_string);
}

function get_studyData() {
	global $LANG;
	
	$qId = isset($_GET['qid']) ? (int)$_GET['qid'] : 0;
	$access_key = get_accessKey();
	$key_index = unserialize(file_get_contents(FILE_STUDY_INDEX));
	$key = $access_key ?: '~open';
	
	if(!isset($key_index[$key]))
		return ['notFound' => true, 'error' => $LANG->error_wrong_accessKey];
	
	$ids = $key_index[$key];
	
	if(!isset($_GET['id'])) { //when link has no study id
		if(!$qId) { //when link has no questionnaire id
			if(count($ids) === 1) { //when access key yields only one entry
				$study = load_study($ids[0]);
				if(!$study)
					return ['notFound' => true];
				return [
					'accessKey' => $access_key,
					'study' => $study,
					'questionnaire' => null
				];
			}
			else
				return ['notFound' => true];
		}
		
		//search for questionnaire id:
		
		foreach($ids as $load_id) {
			$study = load_study($load_id);
			if(!$study)
				continue;
			$questionnaire = get_questionnaire($study, $qId);
			if($questionnaire)
				return [
					'accessKey' => $access_key,
					'study' => $study,
					'questionnaire' => $questionnaire
				];
		}
	}
	else { //when link has study id
		$study_id = (int)$_GET['id'];
		foreach($ids as $load_id) {
			if($study_id == $load_id) {
				$study = load_study($load_id);
				if(!$study)
					return ['notFound' => true];
				
				
				if(!$qId || !($questionnaire = get_questionnaire($study, $qId))) //when link has no questionnaire id
					return [
						'accessKey' => $access_key,
						'study' => $study,
						'questionnaire' => null
					];
				else //when link has questionnaire id
					return [
						'accessKey' => $access_key,
						'study' => $study,
						'questionnaire' => $questionnaire
					];
			}
		}
	}
	return ['notFound' => true];
}

function get_study($study_id, $access_key, $gotoAppInstall=true) {
	global $LANG;
	global $error;
	
	if(!isset($study_id)) {
		$error = $LANG->error_unknown;
		return null;
	}
	
	$key = $access_key ? $access_key : '~open';
	$is_accessible = false;
	
	$key_index = unserialize(file_get_contents(FILE_STUDY_INDEX));
	if(isset($key_index[$key])) {
		$ids = $key_index[$key];
		
		foreach($ids as $load_id) {
			if($study_id == $load_id) {
				$is_accessible = true;
				break;
			}
		}
	}
	
	if(!$is_accessible) {
		$error = $LANG->error_wrong_accessKey;
		return null;
	}
	else {
		$json_string = file_get_contents(get_file_studyConfig($study_id));
		
		if(($study = json_decode($json_string))) {
			if(isset($study->publishedWeb) && !$study->publishedWeb && $gotoAppInstall) {
				global $server_name;
				require 'pages/app_install.php';
				return null;
			}
		}
		else {
			$error = $LANG->error_unknown;
			return null;
		}
	}
	
	return $study;
}


function save_dataset($type, $participant, $study, $questionnaire=null, $dataset_responses=null) {
	$access_key = get_accessKey();
	
	$responses = (object)[
		'model' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
	];
	if(isset($dataset_responses)) {
		foreach($dataset_responses as $k => $v) {
			$responses->{$k} = interpret_inputValue($v);
		}
	}
	$json = (object)[
		'userId' => $participant,
		'appType' => 'Web-NOJS',
		'appVersion' => SERVER_VERSION,
		'serverVersion' => SERVER_VERSION,
		'dataset' => [(object)[
			'dataSetId' => 0,
			'studyId' => $study->id,
			'studyVersion' => $study->version,
			'studySubVersion' => $study->subVersion,
			'accessKey' => ($access_key) ?: '',
			'questionnaireName' => $questionnaire ? $questionnaire->title : null,
			'questionnaireInternalId' => $questionnaire ? $questionnaire->internalId : null,
			'eventType' => $type,
			'responseTime' => get_milliseconds(),
			'responses' => $responses
		]],
	];
	
	$html = true;
	return require_once 'datasets.php';
}
?>
