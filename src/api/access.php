<?php

namespace api;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

use backend\Configs;
use backend\CreateDataSet;
use backend\JsonOutput;

if(!Configs::getDataStore()->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}
if(!Configs::getDataStore()->isReady()) {
	echo JsonOutput::error('Server is not ready.');
	return;
}

if(!isset($_POST['study_id']) || !isset($_POST['page_name'])) {
	echo JsonOutput::error('Missing data');
	return;
}
$studyId = (int) $_POST['study_id'];
$pageName = $_POST['page_name'];

if(Configs::getDataStore()->getStudyStore()->isLocked($studyId)) {
	echo JsonOutput::error("This study is locked");
	return;
}

echo JsonOutput::successObj(CreateDataSet::saveWebAccess($studyId, $pageName));
