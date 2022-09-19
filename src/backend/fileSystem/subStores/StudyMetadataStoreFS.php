<?php

namespace backend\fileSystem\subStores;

use backend\Main;
use backend\CriticalError;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\subStores\StudyMetadataStore;

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
	 * @throws CriticalError
	 */
	private function loadMetadata() {
		if($this->metadata)
			return;
		$metadataPath = PathsFS::fileStudyMetadata($this->studyId);
		if(!file_exists($metadataPath))
			throw new CriticalError('Study does not exist');
		
		$this->metadata = unserialize(file_get_contents($metadataPath));
	}
	
	public function __construct($studyId) {
		$this->studyId = $studyId;
	}
	
	public function updateMetadata($study) {
		$this->metadata =  [
			'version' => (int) ($study->version ?? 0),
			'published' => $study->published ?? false,
			'accessKeys' => $study->accessKeys ?? [],
			'lastBackup' => Main::getMilliseconds()
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
	
	public function getAccessKeys(): array {
		$this->loadMetadata();
		return $this->metadata['accessKeys'] ?? [];
	}
	
	public function getLastBackup(): int {
		$this->loadMetadata();
		return $this->metadata['lastBackup'] ?? 0;
	}
}