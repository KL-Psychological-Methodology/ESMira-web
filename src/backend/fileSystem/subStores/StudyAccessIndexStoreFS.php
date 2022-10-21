<?php

namespace backend\fileSystem\subStores;

use backend\fileSystem\loader\StudyAccessKeyIndexLoader;
use backend\subStores\StudyAccessIndexStore;
use stdClass;

const OPEN_STUDIES_KEY = '~open';

class StudyAccessIndexStoreFS implements StudyAccessIndexStore {
	private function getQuestionnaireKey(int $internalId): string {
		return '~'.$internalId;
	}
	
	/**
	 * @var boolean
	 */
	private $wasChanged = false;
	
	/**
	 * @var array
	 */
	private $studyIndex;
	
	public function __construct() {
		$this->studyIndex = StudyAccessKeyIndexLoader::importFile();
	}
	
	public function getStudyIds(string $key = OPEN_STUDIES_KEY): array {
		return $this->studyIndex[$key ?: OPEN_STUDIES_KEY] ?? [];
	}
	public function accessKeyExists(string $key): bool {
		return isset($this->studyIndex[$key]);
	}
	public function getStudyIdForQuestionnaireId(int $internalId): int {
		$key = $this->getQuestionnaireKey($internalId);
		return $this->studyIndex[$key][0] ?? -1; //it will always be an array with only one entry to stay consistent with the overall structure
	}
	
	public function add(int $studyId, string $value = OPEN_STUDIES_KEY) {
		$this->wasChanged = true;
		if(empty($value))
			$value = OPEN_STUDIES_KEY;
		if(!isset($this->studyIndex[$value]))
			$this->studyIndex[$value] = [$studyId];
		else if(!in_array($studyId, $this->studyIndex[$value]))
			$this->studyIndex[$value][] = $studyId;
	}
	public function addQuestionnaireKeys(stdClass $study) {
		$this->wasChanged = true;
		foreach($study->questionnaires as $q) {
			$key = $this->getQuestionnaireKey($q->internalId);
			
			$this->studyIndex[$key] = [$study->id]; //this doesnt have to be an array. But we try to stay consistent with the structure of access key entries
		}
	}
	
	public function removeStudy(int $studyId): bool {
		$this->wasChanged = true;
		$wasRemoved = false;
		foreach($this->studyIndex as $key => $subList) {
			if(($listIndex = array_search($studyId, $subList)) !== false) {
				array_splice($this->studyIndex[$key], $listIndex, 1);
				$wasRemoved = true;
			}
			if(!count($this->studyIndex[$key]))
				unset($this->studyIndex[$key]);
		}
		return $wasRemoved;
	}
	
	public function saveChanges() {
		if(!$this->wasChanged)
			return;
		
		StudyAccessKeyIndexLoader::exportFile($this->studyIndex);
	}
}