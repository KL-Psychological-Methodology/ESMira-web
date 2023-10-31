<?php

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

$postInput = Main::getRawPostInput();

if(strlen($postInput) == 0) {
	echo JsonOutput::error('no data');
	return;
}

if(Main::report($postInput))
	echo JsonOutput::successObj();
else
	echo JsonOutput::error('Could not save report');