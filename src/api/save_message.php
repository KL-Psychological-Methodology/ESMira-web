<?php

use backend\exceptions\CriticalException;
use backend\Main;
use backend\Configs;
use backend\JsonOutput;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

if(!Configs::getDataStore()->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}
if(!Configs::getDataStore()->isReady()) {
	echo JsonOutput::error('Server is not ready.');
	return;
}

$rest_json = Main::getRawPostInput();
if(!($json = json_decode($rest_json))) {
	echo JsonOutput::error('Unexpected data');
	return;
}

if(!isset($json->userId) || !isset($json->studyId) || !isset($json->content) || !isset($json->serverVersion)) {
	echo JsonOutput::error('Missing data');
	return;
}

if($json->serverVersion < Main::ACCEPTED_SERVER_VERSION) {
	echo JsonOutput::error('This app is outdated. Aborting');
	return;
}

$userId = $json->userId;
$studyId = $json->studyId;
$content = $json->content;

if(Configs::getDataStore()->getStudyStore()->isLocked($studyId)) {
	echo JsonOutput::error("This study is locked");
	return;
}

if(strlen($content) < 2) {
	echo JsonOutput::error("Message is too short");
	return;
}
else if(!Main::strictCheckInput($userId)) {
	echo JsonOutput::error('User is faulty');
	return;
}

try {
	Configs::getDataStore()->getMessagesStore()->receiveMessage($studyId, $userId, $userId, $content);
}
catch(CriticalException $e) {
	echo JsonOutput::error($e->getMessage());
	return;
}
catch(Throwable $e) {
	Main::reportError($e);
	echo JsonOutput::error('Internal server error');
	return;
}
echo JsonOutput::successObj();