<?php

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\NoRewardCodeException;
use backend\JsonOutput;
use backend\Main;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

if(!Configs::getDataStore()->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}
if(!Configs::getDataStore()->isReady()) {
	echo JsonOutput::error('Server is not ready.');
	return;
}

$jsonString = Main::getRawPostInput();
if(!($json = json_decode($jsonString)) || !isset($json->studyId) || !isset($json->userId) || !isset($json->serverVersion)) {
	echo JsonOutput::error('Missing data');
	return;
}

$studyId = (int) $json->studyId;
$userId = $json->userId;

$dataStore = Configs::getDataStore();
$studyStore = $dataStore->getStudyStore();
$userDataStore = $dataStore->getUserDataStore($userId);


if($studyStore->isLocked($studyId)) {
	echo JsonOutput::error('This study is locked');
	return;
}




try {
	$code = $userDataStore->generateRewardCode($studyId);
}
catch(NoRewardCodeException $e) {
	$fulfilledQuestionnaires = $e->getFulfilledQuestionnaires();
	echo JsonOutput::successObj([
		'errorCode' => $e->getCode(),
		'errorMessage' => $e->getMessage(),
		'fulfilledQuestionnaires' => empty($fulfilledQuestionnaires) ? new stdClass() : $e->getFulfilledQuestionnaires()
	]);
	return;
}
catch(CriticalException $e) {
	echo JsonOutput::error($e->getMessage(), $e->getCode());
	return;
}



echo JsonOutput::successObj(['errorCode' => 0, 'code' => $code]);