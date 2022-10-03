<?php

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\NoRewardCodeException;
use backend\JsonOutput;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

if(!Configs::getDataStore()->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}

if(!isset($_POST['studyId']) || !isset($_POST['userId'])) {
	echo JsonOutput::error('Missing data');
	return;
}

$studyId = (int) $_POST['studyId'];
$userId = $_POST['userId'];

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
	echo JsonOutput::error($e->getMessage(), $e->getCode());
	return;
}
catch(CriticalException $e) {
	echo JsonOutput::error($e->getMessage(), $e->getCode());
	return;
}



echo JsonOutput::successObj($code);