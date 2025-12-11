<?php

use backend\Configs;
use backend\JsonOutput;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

if(!Configs::getDataStore()->isReady()) {
	echo JsonOutput::error('Server is not ready.');
	return;
}

echo JsonOutput::successString(Configs::getDataStore()->getServerStatisticsStore()->getStatisticsAsJsonString());