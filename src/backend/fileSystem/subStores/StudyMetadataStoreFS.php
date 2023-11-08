<?php

namespace backend\fileSystem\subStores;

use backend\Main;
use backend\exceptions\CriticalException;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\Permission;
use backend\subStores\StudyMetadataStore;
use stdClass;

//this class is mainly there to be compatible with a database were we would not load all metadata all at once
class StudyMetadataStoreFS implements StudyMetadataStore {
	/**
	 * @var array
	 */
	private $metadata = null;
	
	/**
	 * @var int
	 */
	private $studyId;
	
	/**
	 * @throws CriticalException
	 */
	private function loadMetadata() {
		if($this->metadata)
			return;
		$metadataPath = PathsFS::fileStudyMetadata($this->studyId);
		if(!file_exists($metadataPath))
			throw new CriticalException('Study does not exist');
		
		$this->metadata = unserialize(file_get_contents($metadataPath));
	}
	
	public function __construct(int $studyId) {
		$this->studyId = $studyId;
	}
	
	public function updateMetadata(stdClass $study) {
		$this->metadata =  [
			'version' => (int) ($study->version ?? 0),
			'published' => $study->published ?? false,
			'hasQuestionnaires' => isset($study->questionnaires) && count($study->questionnaires),
			'title' => $study->title ?? 'Error',
			'accessKeys' => $study->accessKeys ?? [],
			'lastSavedBy' => Permission::getAccountName()
		];
		FileSystemBasics::writeFile(PathsFS::fileStudyMetadata($this->studyId), serialize($this->metadata));
	}
	
	public function getVersion(): int {
		$this->loadMetadata();
		return $this->metadata['version'] ?? '0.0';
	}
	
	public function isPublished(): bool {
		$this->loadMetadata();
		return $this->metadata['published'] ?? false;
	}
	
	public function hasQuestionnaires(): bool {
		$this->loadMetadata();
		return $this->metadata['hasQuestionnaires'] ?? false;
	}
	
	public function getAccessKeys(): array {
		$this->loadMetadata();
		return $this->metadata['accessKeys'] ?? [];
	}
	
	public function getTitle(): string {
		$this->loadMetadata();
		return $this->metadata['title'] ?? 'Error';
	}
}