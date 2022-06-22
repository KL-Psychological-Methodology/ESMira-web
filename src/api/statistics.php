<?php

use backend\Configs;
use backend\JsonOutput;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

if(!Configs::getDataStore()->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}

if(!isset($_GET['id'])) {
	echo JsonOutput::error('Missing data');
	return;
}

$studyId = (int) $_GET['id'];


try {
	$metadata = Configs::getDataStore()->getStudyMetadataStore($studyId);
	$accessKeys = $metadata->getAccessKeys();
	if(sizeof($accessKeys) && (!isset($_GET['access_key']) || !in_array(strtolower($_GET['access_key']), $accessKeys))) {
		echo JsonOutput::error("Wrong accessKey: $_GET[access_key]");
		return;
	}
}
catch(Exception $e) {
	echo JsonOutput::error($e->getMessage());
	return;
}

try {
	echo JsonOutput::successObj(Configs::getDataStore()->getStudyStatisticsStore($studyId)->getStatistics());
}
catch(Exception $e) {
	echo JsonOutput::error($e->getMessage());
}