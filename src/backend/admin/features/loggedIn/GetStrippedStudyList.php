<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Configs;
use backend\Permission;

class GetStrippedStudyList extends IsLoggedIn {
	
	private function stripStudy(string $studyString): array {
		$json = json_decode($studyString);
		$stripped = [
			'id' => $json->id ?? -1,
			'published' => $json->published ?? false,
			'title' => $json->title ?? 'Error',
			'accessKeys' => $json->accessKeys ?? [],
			'questionnaires' => []
		];
		foreach($json->questionnaires as $questionnaire) {
			$stripped['questionnaires'][] = [
				'title' => $questionnaire->title ?? 'Error',
				'internalId' => $questionnaire->internalId ?? -1
			];
		}
		return $stripped;
	}
	
	private function getStudiesFromPermissions(): array {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$indexedStudies = [];
		$studiesJson = [];
		$permissions = Permission::getPermissions();
		if(isset($permissions['read'])) {
			foreach($permissions['read'] as $studyId) {
				$indexedStudies[$studyId] = true;
				$studiesJson[] = $this->stripStudy($studyStore->getStudyConfigAsJson($studyId));
			}
		}
		if(isset($permissions['msg'])) {
			foreach($permissions['msg'] as $studyId) {
				if(!isset($indexedStudies[$studyId])) {
					$indexedStudies[$studyId] = true;
					$studiesJson[] = $this->stripStudy($studyStore->getStudyConfigAsJson($studyId));
				}
			}
		}
		if(isset($permissions['write'])) {
			foreach($permissions['write'] as $studyId) {
				if(!isset($indexedStudies[$studyId]))
					$studiesJson[] = $this->stripStudy($studyStore->getStudyConfigAsJson($studyId));
			}
		}
		
		return $studiesJson;
	}
	
	function exec(): array {
		if(Permission::isAdmin()) {
			$studyStore = Configs::getDataStore()->getStudyStore();
			$studiesJson = [];
			$studies = $studyStore->getStudyIdList();
			foreach($studies as $studyId) {
				$studiesJson[] = $this->stripStudy($studyStore->getStudyConfigAsJson($studyId));
			}
			return $studiesJson;
		}
		else
			return $this->getStudiesFromPermissions();
	}
}