<?php

use backend\Configs;
use backend\CriticalError;
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

foreach($data as $studyId => $line) {
	if(!isset($line->version) || !isset($line->msgTimestamp)) {
		echo JsonOutput::error('Missing line data');
		return;
	}
	
	if($studyStore->isLocked($studyId))
		continue;
	
	try {
		$metadata = $dataStore->getStudyMetadataStore($studyId);
		
		$version = $line->version;
		$forceStudyUpdate = isset($line->forceStudyUpdate) && $line->forceStudyUpdate;
		$lastMessageTimestamp = $line->msgTimestamp;
		
		$accessKeys = $metadata->getAccessKeys();
		if(!empty($accessKeys) && !in_array(strtolower($line->accessKey ?? ''), $accessKeys)) {
			echo JsonOutput::error('Wrong accessKey: ' .($line->accessKey ?? ''));
			return;
		}
		
		$line = [];
		
		
		//messages:
		$outputMessages = [];
		$messagesStore->updateOrArchivePendingMessages($studyId, $userId, function(Message $message) use ($lastMessageTimestamp, &$outputMessages): bool {
			if($lastMessageTimestamp >= $message->sent)
				return false;
			
			$message->read = Main::getMilliseconds();
			$message->delivered += 1;
			
			$newMessage = $message; //clones the variable
			$newMessage->from = 'server';
			$outputMessages[] = $newMessage;
			return true;
			
		});
		if(count($outputMessages))
			$line['msgs'] = $outputMessages;
		
		
		//studies:
		if($forceStudyUpdate || $metadata->getVersion() > $version) {
			//TODO: $study_json is a String, so we need to turn it into an object first or JSON will format it as a string.
			// This is a waste of performance. So a better solution would be to just concat the JSON string manually which is ugly
			$line['study'] = $studyStore->getStudyConfig($studyId);
		}
		
		
		if(!empty($line))
			$output->{$studyId} = $line;
	}
	catch(CriticalError $e) {
		echo JsonOutput::error($e->getMessage());
		return;
	}
}
echo JsonOutput::successObj($output);