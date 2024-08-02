<?php

use backend\Main;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\JsonOutput;

require_once dirname(__FILE__, 2) . '/backend/autoload.php';

if(!Configs::getDataStore()->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}
if(!Configs::getDataStore()->isReady()) {
	echo JsonOutput::error('Server is not ready.');
	return;
}

$postInput = Main::getRawPostInput();
if(!($json = json_decode($postInput))) {
	echo JsonOutput::error('Unexpected data.');
	return;
}

if(!isset($json->studyId) || !isset($json->content) || !isset($json->serverVersion)) {
	echo JsonOutput::error('Missing data.');
	return;
}

if($json->serverVersion < Main::ACCEPTED_SERVER_VERSION) {
	echo JsonOutput::error('This app is outdated. Aborting.');
	return;
}

$studyId = $json->studyId;
$content = $json->content;

if(Configs::getDataStore()->getStudyStore()->isLocked($studyId)) {
	echo JsonOutput::error('This study is locked.');
	return;
}

if(strlen($content) < 2) {
	echo JsonOutput::error('Message is too short.');
	return;
}

try {
	Configs::getDataStore()->getMerlinLogsStore()->receiveMerlinLog($studyId, $content);
} catch(CriticalException $e) {
	echo JsonOutput::error($e->getMessage());
	return;
} catch(Throwable $e) {
	Main::reportError($e);
	echo JsonOutput::error('Internal server error.');
	return;
}
echo JsonOutput::successObj();

