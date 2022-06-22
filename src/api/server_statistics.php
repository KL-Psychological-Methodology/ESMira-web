<?php

use backend\Configs;
use backend\JsonOutput;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

if(!Configs::getDataStore()->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}

echo JsonOutput::successString(Configs::getDataStore()->getServerStatisticsStore()->getStatisticsAsJsonString());