<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\Permission;

class GetStrippedStudyList extends IsLoggedIn {
	
	/**
	 * @throws CriticalException
	 */
	private function getStrippedStudy(int $studyId): array {
		$studyMetadataStore = Configs::getDataStore()->getStudyMetadataStore($studyId);
		return [
			'id' => $studyId,
			'published' => $studyMetadataStore->isPublished(),
			'questionnaires' => $studyMetadataStore->hasQuestionnaires() ? [[]] : [],
			'title' => $studyMetadataStore->getTitle(),
			'accessKeys' => $studyMetadataStore->getAccessKeys(),
			'owner' => $studyMetadataStore->getOwner(),
			'lastSavedBy' => $studyMetadataStore->getLastSavedBy(),
			'lastSavedAt' => $studyMetadataStore->getLastSavedAt(),
			'createdTimestamp' => $studyMetadataStore->getCreatedTimestamp(),
			'studyTag' => $studyMetadataStore->getStudyTag()
		];
	}
	
	/**
	 * @throws CriticalException
	 */
	private function getStudiesFromPermissions(): array {
		$indexedStudies = [];
		$studiesJson = [];
		$permissions = Permission::getPermissions();
		if(isset($permissions['read'])) {
			foreach($permissions['read'] as $studyId) {
				$indexedStudies[$studyId] = true;
				$studiesJson[] = $this->getStrippedStudy($studyId);
			}
		}
		if(isset($permissions['msg'])) {
			foreach($permissions['msg'] as $studyId) {
				if(!isset($indexedStudies[$studyId])) {
					$indexedStudies[$studyId] = true;
					$studiesJson[] = $this->getStrippedStudy($studyId);
				}
			}
		}
		if(isset($permissions['write'])) {
			foreach($permissions['write'] as $studyId) {
				if(!isset($indexedStudies[$studyId]))
					$studiesJson[] = $this->getStrippedStudy($studyId);
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
				$studiesJson[] = $this->getStrippedStudy($studyId);
			}
			return $studiesJson;
		}
		else
			return $this->getStudiesFromPermissions();
	}
}