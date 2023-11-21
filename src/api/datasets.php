<?php
ignore_user_abort(true);
set_time_limit(0);

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

use backend\Main;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\JsonOutput;
use backend\CreateDataSet;

if(!Configs::getDataStore()->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}
if(!Configs::getDataStore()->isReady()) {
	echo JsonOutput::error('Server is not ready.');
	return;
}

if(!($json = json_decode(Main::getRawPostInput()))) {
	echo JsonOutput::error('Unexpected data format');
	return;
}

$dataSet = new CreateDataSet();
try {
	$dataSet->prepare($json);
	$dataSet->exec();
	echo JsonOutput::successObj([
		'states' => $dataSet->output,
		'tokens' => $dataSet->userDataStore->getNewStudyTokens() ?: new stdClass() //stdClass makes sure it stays an object even when its empty
	]);
}
catch(CriticalException $e) {
	$dataSet->close();
	echo JsonOutput::error($e->getMessage());
	return;
}
catch(Throwable $e) {
	$dataSet->close();
	Main::reportError($e, "There was an unexpected error when trying to save data.\nJson:\n" .Main::getRawPostInput());
	echo JsonOutput::error($e->getMessage());
	return;
}
