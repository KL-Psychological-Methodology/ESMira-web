<?php

use backend\Configs;

$studyStore = Configs::getDataStore()->getStudyStore();
$studies = $studyStore->getStudyIdList();
foreach($studies as $studyId) {
	$study = $studyStore->getStudyConfig($studyId);
	$studyMetadataStore = Configs::getDataStore()->getStudyMetadataStore($studyId);
	$studyMetadataStore->updateMetadata($study);
}