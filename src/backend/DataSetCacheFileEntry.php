<?php

namespace backend;

class DataSetCacheFileEntry {
	public $studyId;
	public $internalPath;
	public $identifier;
	
	public function __construct(int $studyId, string $path, int $identifier) {
		$this->studyId = $studyId;
		$this->internalPath = $path;
		$this->identifier = $identifier;
	}
}