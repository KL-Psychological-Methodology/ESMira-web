<?php

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\JsonOutput;
use backend\Main;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

$datastore = Configs::getDataStore();

if (!$datastore->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}

$pluginName = $_GET['plugin'] ?? '';
$pageName = $_GET['page'] ?? '';

try {
	echo $datastore->getPluginStore()->getFrontendCode($pluginName, $pageName);
	return;
} catch (CriticalException $e) {
	echo JsonOutput::error($e->getMessage());
	return;
} catch (Throwable $e) {
	Main::reportError($e);
	echo JsonOutput::error('Internal server error');
	return;
}