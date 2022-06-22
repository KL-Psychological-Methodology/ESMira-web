<?php

namespace backend\fileSystem\subStores;

use backend\dataClasses\StudyStatisticsEntry;
use backend\fileSystem\loader\StudyStatisticsMetadataLoader;
use backend\subStores\StudyStatisticsMetadataStore;
use stdClass;

class StudyStatisticsMetadataStoreFS implements StudyStatisticsMetadataStore {
	/**
	 * @var int
	 */
	private $studyId;
	/**
	 * @var [[StudyStatisticsEntry]]
	 */
	private $metadata;
	public function __construct(int $studyId) {
		$this->studyId = $studyId;
		$this->metadata = [];
	}
	
	public function loadMetadataCollection(): array {
		return StudyStatisticsMetadataLoader::importFile($this->studyId);
	}
	
	public function addMetadataEntry(string $key, StudyStatisticsEntry $entry) {
		if(!isset($this->metadata[$key]))
			$this->metadata[$key] = [];
		$this->metadata[$key][] = $entry;
	}
	
	public function saveChanges() {
		StudyStatisticsMetadataLoader::exportFile($this->studyId, $this->metadata);
	}
}