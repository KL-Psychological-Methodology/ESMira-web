<?php

use backend\Configs;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\Permission;

//Note: PathsFS::fileStudyCreateMetadata did not exist in the last version and if the server is using Zend, when this is executed, the old PathsFS will be loaded.
// So we can not use StudyMetadataStoreFS::updateMetadata and save everything manually instead. From now on we call opcache_reset(), so this should not be necessary anymore.

$studyStore = Configs::getDataStore()->getStudyStore();
$studies = $studyStore->getStudyIdList();
foreach($studies as $studyId) {
	$study = $studyStore->getStudyConfig($studyId);
	
	$metadata =  [
		'version' => (int) ($study->version ?? 0),
		'published' => $study->published ?? false,
		'hasQuestionnaires' => isset($study->questionnaires) && count($study->questionnaires),
		'title' => $study->title ?? 'Error',
		'accessKeys' => $study->accessKeys ?? [],
		'lastSavedBy' => Permission::getAccountName()
	];
	FileSystemBasics::writeFile(PathsFS::fileStudyMetadata($studyId), serialize($metadata));
	
	
	$createPath = PathsFS::folderStudies()."$studyId/.create_metadata";
	if(!file_exists($createPath)) {
		$createMetadata = [
			'timestamp' => time(),
			'owner' => Permission::getAccountName()
		];
		FileSystemBasics::writeFile($createPath, serialize($createMetadata));
	}
	
	//Cannot use this:
//	$studyMetadataStore = Configs::getDataStore()->getStudyMetadataStore($studyId);
//	$studyMetadataStore->updateMetadata($study);
}