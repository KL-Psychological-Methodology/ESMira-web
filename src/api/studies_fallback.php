<?php

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\JsonOutput;
use backend\Main;

require_once dirname(__FILE__, 2) . '/backend/autoload.php';

if (!Configs::getDataStore()->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}

if (!isset($_GET['fromUrl'])) {
	echo JsonOutput::error("Missing data");
	return;
}

$fromUrl = $_GET['fromUrl'];

$studiesJson = [];
$studyStore = Configs::getDataStore()->getFallbackStudyStore($fromUrl);

try {
	$key = isset($_Get['access_key']) ? strtolower(trim($_Get['access_key'])) : '';
	$lang = Main::getLang(false);

	$ids = Configs::getDataStore()->getFallbackStudyAccessIndexStore($fromUrl)->getStudyIds($key);
	foreach ($ids as $studyId) {
		$studiesJson[] = $studyStore->getStudyLangConfigAsJson($studyId, $lang);
	}
} catch (CriticalException $e) {
	echo JsonOutput::error($e->getMessage());
	return;
} catch (Throwable $e) {
	Main::reportError($e);
	echo JsonOutput::error('Internal server error');
	return;
}

echo JsonOutput::successString('[' . implode(',', $studiesJson) . ']');
