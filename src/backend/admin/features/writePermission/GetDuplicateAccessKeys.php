<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Configs;

class GetDuplicateAccessKeys extends HasWritePermission {
	function exec(): array
	{
		$studyId = $this->studyId;
		$dataStore = Configs::getDataStore();
		$accessIndexStore = $dataStore->getStudyAccessIndexStore();

		$accessKeys = [];
		if(isset($_POST['accessKeys'])) {
			error_log($_POST['accessKeys']);
			$accessKeys = explode(",", $_POST['accessKeys']);
		} else {
			$metadataStore = $dataStore->getStudyMetadataStore($studyId);
			$accessKeys = $metadataStore->getAccessKeys();
		}

		error_log(json_encode($accessKeys));

		$duplicateAccessKeys = [];

		foreach($accessKeys as $accessKey) {
			$studiesWithKey = $accessIndexStore->getStudyIds($accessKey);
			$studiesCount = count($studiesWithKey);
			if($studiesCount > 1 || ($studiesCount == 1 && $studiesWithKey[0] != $studyId)) {
				$duplicateAccessKeys[] = $accessKey;
			}
		}

		return $duplicateAccessKeys;
	}
}