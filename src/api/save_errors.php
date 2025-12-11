<?php

use backend\Main;
use backend\Configs;
use backend\JsonOutput;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

$dataStore = Configs::getDataStore();

if(!$dataStore->isReady()) {
	echo JsonOutput::error('Server is not ready.');
	return;
}

$postInput = Main::getRawPostInput();

$dataStore->getPluginStore()->handleErrorReport($postInput);

if(strlen($postInput) == 0) {
	echo JsonOutput::error('no data');
	return;
}

if(Main::report($postInput))
	echo JsonOutput::successObj();
else
	echo JsonOutput::error('Could not save report');