<?php

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\dataClasses\Message;
use backend\JsonOutput;
use backend\Main;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

if(!Configs::getDataStore()->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}


$jsonString = Main::getRawPostInput();
if(!($json = json_decode($jsonString)) || !isset($json->dataset) || !isset($json->userId) || !isset($json->serverVersion)) {
	echo JsonOutput::error('Missing data');
	return;
}

if($json->serverVersion < Main::ACCEPTED_SERVER_VERSION) {
	echo JsonOutput::error('This app is outdated. Aborting');
	return;
}

$data = $json->dataset;
$userId = $json->userId;

$output = new stdClass();

$dataStore = Configs::getDataStore();
$studyStore = $dataStore->getStudyStore();
$messagesStore = $dataStore->getMessagesStore();
$lang = Main::getLang(false);

foreach($data as $studyId => $entry) {
	if(!isset($entry->version) || !isset($entry->msgTimestamp)) {
		echo JsonOutput::error('Missing line data');
		return;
	}
	
	if($studyStore->isLocked($studyId))
		continue;
	
	try {
		$metadata = $dataStore->getStudyMetadataStore($studyId);
		
		$version = $entry->version;
		$forceStudyUpdate = isset($entry->forceStudyUpdate) && $entry->forceStudyUpdate;
		$lastMessageTimestamp = $entry->msgTimestamp;
		$line = [];
		
		
		//messages:
		$outputMessages = [];
		$messagesStore->updateOrArchivePendingMessages($studyId, $userId, function(Message $message) use ($lastMessageTimestamp, &$outputMessages): bool {
			if($lastMessageTimestamp >= $message->sent)
				return false;
			
			$message->read = Main::getMilliseconds();
			$message->delivered += 1;
			
			$outputMessage = clone $message;
			$outputMessage->from = 'server';
			$outputMessages[] = $outputMessage;
			return true;
		});
		if(count($outputMessages))
			$line['msgs'] = $outputMessages;
		
		
		//accessKey:
		$accessKeys = $metadata->getAccessKeys();
		if(!empty($accessKeys) && !in_array(trim(strtolower($entry->accessKey ?? '')), $accessKeys))
			$line['errorCode'] = 'wrongAccessKey';
		
		//studies (only if accessKey is correct):
		else if($forceStudyUpdate || $metadata->getVersion() > $version) {
			//TODO: $study_json is a String, so we need to turn it into an object first or JSON will format it as a string.
			// This is a waste of performance. So a better solution would be to just concat the JSON string manually which is ugly
			$line['study'] = $studyStore->getStudyLangConfig($studyId, $lang);
		}
		
		
		if(!empty($line))
			$output->{$studyId} = $line;
	}
	catch(CriticalException $e) {
		echo JsonOutput::error($e->getMessage());
		return;
	}
}
echo JsonOutput::successObj($output);