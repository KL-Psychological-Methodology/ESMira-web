<?php

namespace backend\subStores;

use backend\CriticalError;

interface StudyMetadataStore {
	/**
	 * @throws CriticalError
	 */
	public function updateMetadata($study);
	
	public function __construct($studyId);
	/**
	 * @throws CriticalError
	 */
	public function getVersion(): int;
	/**
	 * @throws CriticalError
	 */
	public function isPublished(): bool;
	/**
	 * @throws CriticalError
	 */
	public function getAccessKeys(): array;
	/**
	 * @throws CriticalError
	 */
	public function getLastBackup(): int;
}