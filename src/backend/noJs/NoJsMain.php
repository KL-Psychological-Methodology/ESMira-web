<?php

namespace backend\noJs;

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\noJs\pages\StudiesList;
use backend\exceptions\PageFlowException;
use Exception;
use backend\Main;
use backend\CreateDataSet;
use backend\subStores\BaseStudyStore;
use backend\subStores\StudyAccessIndexStore;
use stdClass;

class NoJsMain
{
	static function questionnaireIsActive(stdClass $questionnaire): bool
	{
		return (!isset($questionnaire->publishedWeb) || $questionnaire->publishedWeb)
			&& (!isset($questionnaire->durationStart) || !$questionnaire->durationStart || time() >= $questionnaire->durationStart)
			&& (!isset($questionnaire->durationEnd) || !$questionnaire->durationEnd || time() <= $questionnaire->durationEnd)
			&& isset($questionnaire->pages) && sizeof($questionnaire->pages);
	}

	static function getQuestionnaire(stdClass $study, int $qId)
	{
		foreach ($study->questionnaires as $questionnaire) {
			if ($questionnaire->internalId === $qId) {
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
	static function getStudyData(): StudyData
	{
		$studyStore = Configs::getDataStore()->getStudyStore();
		$studyAccessIndexStore = Configs::getDataStore()->getStudyAccessIndexStore();
		return self::getStudyDataFromStore($studyStore, $studyAccessIndexStore);
	}

	static function getFallbackStudyData(string $encodedUrl): StudyData
	{
		$studyStore = Configs::getDataStore()->getFallbackStudyStore($encodedUrl);
		$studyAccessIndexStore = Configs::getDataStore()->getFallbackStudyAccessIndexStore($encodedUrl);
		return self::getStudyDataFromStore($studyStore,  $studyAccessIndexStore, false);
	}

	static private function getStudyDataFromStore(BaseStudyStore $studyStore, StudyAccessIndexStore $studyAccessIndexStore, bool $forward = true): StudyData
	{
		$accessKey = Main::getAccessKey();
		$idsForAccessKey = $studyAccessIndexStore->getStudyIds($accessKey);
		$lang = Main::getLang(false);

		if (empty($idsForAccessKey) && !empty($accessKey))
			throw new PageFlowException(Lang::get('error_wrong_accessKey'));

		if (isset($_GET['id'])) {
			$targetStudyId = (int) $_GET['id'];
		} else if (isset($_GET['qid'])) {
			$targetStudyId = $studyAccessIndexStore->getStudyIdForQuestionnaireId((int) $_GET['qid']);
		}



		if (isset($targetStudyId) && $targetStudyId) {
			foreach ($idsForAccessKey as $studyId) {
				if ($studyId == $targetStudyId) {
					$study = $studyStore->getStudyLangConfig($studyId, $lang);
					break;
				}
			}
		} else if (count($idsForAccessKey) == 1)
			$study = $studyStore->getStudyLangConfig($idsForAccessKey[0], $lang);

		if (!isset($study)) {
			if (empty($idsForAccessKey) && !empty($accessKey))
				throw new PageFlowException(Lang::get('error_wrong_accessKey'));
			else if ($forward)
				throw new ForwardingException(new StudiesList());
			else
				throw new PageFlowException(Lang::get('error_study_not_found'));
		}

		return (!isset($_GET['qid']) || !($questionnaire = self::getQuestionnaire($study, (int)$_GET['qid'])))
			? new StudyData($accessKey, $study)
			: new StudyData($accessKey, $study, $questionnaire);
	}
}
