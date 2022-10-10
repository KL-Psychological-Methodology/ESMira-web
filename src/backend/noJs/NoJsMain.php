<?php

namespace backend\noJs;

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\noJs\pages\StudiesList;
use backend\exceptions\PageFlowException;
use Exception;
use backend\Main;
use backend\CreateDataSet;
use stdClass;

class NoJsMain {
	static function questionnaireIsActive(stdClass $questionnaire): bool {
		return (!isset($questionnaire->publishedWeb) || $questionnaire->publishedWeb)
			&& (!isset($questionnaire->durationStart) || !$questionnaire->durationStart || time() >= $questionnaire->durationStart)
			&& (!isset($questionnaire->durationEnd) || !$questionnaire->durationEnd || time() <= $questionnaire->durationEnd)
			&& isset($questionnaire->pages) && sizeof($questionnaire->pages);
	}
	
	static function getQuestionnaire(stdClass $study, int $qId) {
		foreach($study->questionnaires as $questionnaire) {
			if($questionnaire->internalId === $qId) {
				return $questionnaire;
			}
		}
		return null;
	}
	
	
	/**
	 * @throws CriticalException
	 * @throws PageFlowException
	 * @throws ForwardingException
	 */
	static function getStudyData(): StudyData {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$studyAccessIndexStore = Configs::getDataStore()->getStudyAccessIndexStore();
		$accessKey = Main::getAccessKey();
		$idsForAccessKey = $studyAccessIndexStore->getStudyIds($accessKey);
		$lang = Main::getLang();
		
		if(empty($idsForAccessKey) && !empty($accessKey))
			throw new PageFlowException(Lang::get('error_wrong_accessKey'));
		
		if(isset($_GET['id']))
			$targetStudyId = (int) $_GET['id'];
		else if(isset($_GET['qid']))
			$targetStudyId = $studyAccessIndexStore->getStudyIdForQuestionnaireId((int) $_GET['qid']);
		
		if(isset($targetStudyId)) {
			foreach($idsForAccessKey as $studyId) {
				if($studyId == $targetStudyId) {
					$study = $studyStore->getStudyLangConfig($studyId, $lang);
					break;
				}
			}
		}
		else if(count($idsForAccessKey) == 1)
			$study = $studyStore->getStudyLangConfig($idsForAccessKey[0], $lang);
		
		if(!isset($study)) {
			if(!empty($accessKey)) //provided access key is valid but for the wrong study
				throw new PageFlowException(Lang::get('error_wrong_accessKey'));
			else
				throw new ForwardingException(new StudiesList());
		}
		
		return (!isset($_GET['qid']) || !($questionnaire = self::getQuestionnaire($study, (int)$_GET['qid'])))
			? new StudyData($accessKey, $study)
			: new StudyData($accessKey, $study, $questionnaire);
	}
	
	
	private static function extractInputValue($v): string {
		if(is_array($v))
			return implode(',', $v);
		else
			return $v;
	}
	
	/**
	 * @throws CriticalException
	 */
	static function saveDataset(string $type, string $userId, stdClass $study, stdClass $questionnaire=null, array $datasetResponses=null) {
		$accessKey = Main::getAccessKey();
		
		$responses = (object)[
			'model' => $_SERVER['HTTP_USER_AGENT'] ?? ''
		];
		if(isset($datasetResponses)) {
			foreach($datasetResponses as $k => $v) {
				$responses->{$k} = self::extractInputValue($v);
			}
		}
		$json = (object)[
			'userId' => $userId,
			'appType' => 'Web-NOJS',
			'appVersion' => (string) Main::SERVER_VERSION,
			'serverVersion' => Main::SERVER_VERSION,
			'dataset' => [(object)[
				'dataSetId' => 0,
				'studyId' => $study->id,
				'studyVersion' => $study->version ?? 0,
				'studySubVersion' => $study->subVersion ?? 0,
				'studyLang' => $study->lang ?? '',
				'accessKey' => ($accessKey) ?: '',
				'questionnaireName' => $questionnaire ? $questionnaire->title : null,
				'questionnaireInternalId' => $questionnaire ? $questionnaire->internalId : null,
				'eventType' => $type,
				'responseTime' => Main::getMilliseconds(),
				'responses' => $responses
			]],
		];
		
		$dataSet = new CreateDataSet($json);
		$dataSet->exec();
		if(empty($dataSet->output))
			throw new CriticalException('No response data');
		else if(!$dataSet->output[0]['success'])
			throw new CriticalException($dataSet->output[0]['error']);
	}
}