<?php

/*
Reason for update:
	3.6.0 makes changes to scheduling. In order to preserve functionality of ongoing studies, all existing studies get set to use legacy scheduling
*/

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\Main;
use backend\ResponsesIndex;

// Copied from SaveStudy.php
// Checks for unique names are removed, as we're handling studies that have already been saved.
function getQuestionnaireIndex(stdClass $questionnaire): ResponsesIndex {
		$questionnaireIndex = new ResponsesIndex();
		
		foreach($questionnaire->pages ?? [] as $page) {
			foreach($page->inputs ?? [] as $input) {
				if(!isset($input->name))
					continue;
				
				$questionnaireIndex->addInput($input);
			}
		}
		
		foreach($questionnaire->sumScores ?? [] as $score) {
			if(!isset($score->name))
				continue;
			
			$questionnaireIndex->addName($score->name);
		}
		
		foreach($questionnaire->virtualInputs ?? [] as $virtualInput) {
			if(!is_string($virtualInput))
				continue;
			
			$questionnaireIndex->addName($virtualInput);
		}
		
		return $questionnaireIndex;
	}

$studyStore = Configs::getDataStore()->getStudyStore();
$studies = $studyStore->getStudyIdList();

foreach($studies as $studyId) {
	try{
		$mainStudy = json_decode($studyStore->getStudyConfigAsJson($studyId), true);
		$mainStudy = array_merge($mainStudy, array("legacyScheduling" => true));
		$study = array("_" => $mainStudy);

		$studyLangs = json_decode($studyStore->getAllLangConfigsAsJson($studyId), true);
		foreach($studyLangs as $langCode=>$langConfig) {
			$langConfig = array_merge($langConfig, array("legacyScheduling" => true));
			$study[$langCode] = $langConfig;
		}

		$keys = [];
		foreach($mainStudy["questionnaires"] as $questionnaire) {
			$keys[$questionnaire["internalId"]] = getQuestionnaireIndex(json_decode(json_encode($questionnaire)));
		}

		$studyStore->saveStudy(json_decode(json_encode($study)), $keys);
	} catch(PageFlowException $e) {
		Main::reportError($e, "There was an error updating study $studyId during server update.");
	} catch(CriticalException $e) {
		Main::reportError($e, "There was an error updating study $studyId during server update.");
	} catch(Throwable $e) {
		Main::reportError($e, "There was an error updating study $studyId during server update.");
	}
}