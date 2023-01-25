<?php

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\JsonOutput;
use backend\Main;
use backend\Permission;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

if(!Configs::getDataStore()->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}


$studiesJson = [];
$studyStore = Configs::getDataStore()->getStudyStore();

try {
	if(isset($_GET['is_loggedIn']) && Permission::isLoggedIn()) {
		if(Permission::isAdmin()) {
			$studies = $studyStore->getStudyIdList();
			foreach($studies as $studyId) {
				$studiesJson[] = $studyStore->getStudyConfigAsJson($studyId);
			}
		}
		else {
			$notTwiceIndex = [];
			$permissions = Permission::getPermissions();
			if(isset($permissions['read'])) {
				foreach($permissions['read'] as $studyId) {
					$notTwiceIndex[$studyId] = true;
					$studiesJson[] = $studyStore->getStudyConfigAsJson($studyId);
				}
			}
			if(isset($permissions['msg'])) {
				foreach($permissions['msg'] as $studyId) {
					if(!isset($notTwiceIndex[$studyId])) {
						$notTwiceIndex[$studyId] = true;
						$studiesJson[] = $studyStore->getStudyConfigAsJson($studyId);
					}
				}
			}
			if(isset($permissions['write'])) {
				foreach($permissions['write'] as $studyId) {
					if(!isset($notTwiceIndex[$studyId]))
						$studiesJson[] = $studyStore->getStudyConfigAsJson($studyId);
				}
			}
		}
	}
	else {
		$key = isset($_GET['access_key']) ? strtolower(trim($_GET['access_key'])) : '';
		$lang = Main::getLang(false);
		
		$ids = Configs::getDataStore()->getStudyAccessIndexStore()->getStudyIds($key);
		foreach($ids as $studyId) {
			$studiesJson[] = $studyStore->getStudyLangConfigAsJson($studyId, $lang);
		}
	}
}
catch(CriticalException $e) {
	echo JsonOutput::error($e->getMessage());
	return;
}
catch(Throwable $e) {
	echo JsonOutput::error('Internal server error');
	return;
}

echo JsonOutput::successString('[' .implode(',', $studiesJson) .']');