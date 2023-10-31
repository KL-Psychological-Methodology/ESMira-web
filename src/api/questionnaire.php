<?php

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\JsonOutput;
use backend\Main;
use backend\noJs\ForwardingException;
use backend\noJs\NoJsMain;
use backend\Permission;
use backend\QuestionnaireSaver;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

if(!Configs::getDataStore()->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}
if(!Configs::getDataStore()->isReady()) {
	echo JsonOutput::error('Server is not ready.');
	return;
}

$studyStore = Configs::getDataStore()->getStudyStore();
try {
	if(isset($_GET['id']) && isset($_GET['qid']) && Permission::isLoggedIn()) {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$lang = Main::getLang(false);
		
		$study = $studyStore->getStudyLangConfig((int) $_GET['id'], $lang);
		$questionnaire = NoJsMain::getQuestionnaire($study, (int) $_GET['qid']);
		if(!$questionnaire)
			throw new CriticalException("Questionnaire $_GET[qid] does not exist");
		$doForwarding = !isset($_GET['demo']);
	}
	else {
		$studyData = NoJsMain::getStudyData();
		
		$study = $studyData->study;
		$questionnaire = $studyData->questionnaire;
		$doForwarding = true;
	}
	$studyId = $study->id;
	$questionnaireId = $questionnaire->internalId;
	
	$inputObj = new QuestionnaireSaver($study, $questionnaire, $doForwarding);
	if($inputObj->finishActionNeeded()) {
		$missingInput = $inputObj->doPageFinishActions('Web', $doForwarding);
	}
	
	if($inputObj->isCompleted) {
		echo JsonOutput::successObj([
			'dataType' => 'finished',
			'pageTitle' => $questionnaire->title,
		]);
	}
	else {
		echo JsonOutput::successObj([
			'dataType' => 'questionnaire',
			'sid' => $inputObj->getSessionUrlParameter(),
			'currentPageInt' => $inputObj->currentPageInt,
			'pageHtml' => $inputObj->drawPage(),
			'pageTitle' => $inputObj->getTitle(),
			'missingInput' => isset($missingInput) ? $missingInput->name : null
		]);
	}
}
catch(CriticalException $e) {
	echo JsonOutput::error($e->getMessage());
}
catch(PageFlowException $e) {
	echo JsonOutput::error($e->getMessage());
}
catch(ForwardingException $e) {
	$page = $e->getPage();
	echo JsonOutput::successObj([
		'dataType' => 'forwarded',
		'pageHtml' => $page->getContent(),
		'pageTitle' => $page->getTitle(),
	]);
}