<?php

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\JsonOutput;
use backend\Main;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

$datastore = Configs::getDataStore();

if(!Configs::getDataStore()->isReady()) {
	echo JsonOutput::error('Server is not ready.');
	return;
}

$pluginId = $_GET['plugin'] ?? '';
$apiName = $_GET['api'] ?? '';

try {
	$datastore->getPluginStore()->runPluginApi($pluginId, $apiName);
	return;
} catch (CriticalException $e) {
	echo JsonOutput::error($e->getMessage());
	return;
} catch (Throwable $e) {
	Main::reportError($e);
	echo JsonOutput::error('Internal server error');
	return;
}