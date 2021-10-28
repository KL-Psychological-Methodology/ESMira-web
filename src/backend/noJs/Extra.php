<?php

namespace backend\noJs;

use Exception;
use backend\Base;
use backend\Files;
use backend\noJs\ForwardingException;
use backend\noJs\Lang;
use backend\noJs\pages\AppInstall;
use backend\CreateDataSet;

class Extra {
	static function questionnaire_isActive($questionnaire) {
		return (!isset($questionnaire->publishedWeb) || $questionnaire->publishedWeb)
			&& (!isset($questionnaire->durationStart) || !$questionnaire->durationStart || time() >= $questionnaire->durationStart)
			&& (!isset($questionnaire->durationEnd) || !$questionnaire->durationEnd || time() <= $questionnaire->durationEnd)
			&& isset($questionnaire->pages) && sizeof($questionnaire->pages);
	}
	static function remove_postHeader() {
		//remove POST-data from history and reload page:
		header('Location:' . (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		exit();
	}
	
	static function get_questionnaire($study, $qId) {
		foreach($study->questionnaires as $questionnaire) {
			if($questionnaire->internalId === $qId) {
				return $questionnaire;
			}
		}
		return null;
	}
	static function load_study($study_id) {
		$json_string = file_get_contents(Files::get_file_studyConfig($study_id));
		if(!$json_string)
			return null;
		return json_decode($json_string);
	}
	
	static function get_studyData() {
		$qId = isset($_GET['qid']) ? (int)$_GET['qid'] : 0;
		$access_key = Base::get_accessKey();
		$key_index = unserialize(file_get_contents(Files::FILE_STUDY_INDEX));
		$key = $access_key ?: '~open';
		
		if(!isset($key_index[$key]))
			return ['notFound' => true, 'error' => Lang::get('error_wrong_accessKey')];
		
		$ids = $key_index[$key];
		
		if(!isset($_GET['id'])) { //when link has no study id
			if(!$qId) { //when link has no questionnaire id
				if(count($ids) === 1) { //when access key yields only one entry
					$study = self::load_study($ids[0]);
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
				$study = self::load_study($load_id);
				if(!$study)
					continue;
				$questionnaire = self::get_questionnaire($study, $qId);
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
					$study = self::load_study($load_id);
					if(!$study)
						return ['notFound' => true];
					
					
					if(!$qId || !($questionnaire = self::get_questionnaire($study, $qId))) //when link has no questionnaire id
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
	
	static function save_dataset($type, $participant, $study, $questionnaire=null, $dataset_responses=null) {
		$access_key = Base::get_accessKey();
		
		$responses = (object)[
			'model' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
		];
		if(isset($dataset_responses)) {
			foreach($dataset_responses as $k => $v) {
				$responses->{$k} = self::interpret_inputValue($v);
			}
		}
		$json = (object)[
			'userId' => $participant,
			'appType' => 'Web-NOJS',
			'appVersion' => Base::SERVER_VERSION,
			'serverVersion' => Base::SERVER_VERSION,
			'dataset' => [(object)[
				'dataSetId' => 0,
				'studyId' => $study->id,
				'studyVersion' => $study->version,
				'studySubVersion' => $study->subVersion,
				'studyLang' => isset($study->lang) ? $study->lang : '',
				'accessKey' => ($access_key) ?: '',
				'questionnaireName' => $questionnaire ? $questionnaire->title : null,
				'questionnaireInternalId' => $questionnaire ? $questionnaire->internalId : null,
				'eventType' => $type,
				'responseTime' => Base::get_milliseconds(),
				'responses' => $responses
			]],
		];
		
		try {
			new CreateDataSet($json);
			return true;
		}
		catch(Exception $e) {
			return false;
		}
	}
	
	public static function interpret_inputValue($v) {
		if(is_array($v))
			return implode(',', $v);
		else
			return $v;
	}
}