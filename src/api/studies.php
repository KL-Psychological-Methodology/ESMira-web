<?php

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\JsonOutput;
use backend\Main;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

if(!Configs::getDataStore()->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}


$studiesJson = [];
$studyStore = Configs::getDataStore()->getStudyStore();

try {
	$key = isset($_GET['access_key']) ? strtolower(trim($_GET['access_key'])) : '';
	$lang = Main::getLang(false);
	
	$ids = Configs::getDataStore()->getStudyAccessIndexStore()->getStudyIds($key);
	foreach($ids as $studyId) {
		$studiesJson[] = $studyStore->getStudyLangConfigAsJson($studyId, $lang);
	}
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

echo JsonOutput::successString('[' .implode(',', $studiesJson) .']');