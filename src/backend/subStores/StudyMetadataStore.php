<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;

interface StudyMetadataStore {
	/**
	 * @throws CriticalException
	 */
	public function updateMetadata($study);
	
	public function __construct($studyId);
	/**
	 * @throws \backend\exceptions\CriticalException
	 */
	public function getVersion(): int;
	/**
	 * @throws \backend\exceptions\CriticalException
	 */
	public function isPublished(): bool;
	/**
	 * @throws \backend\exceptions\CriticalException
	 */
	public function getAccessKeys(): array;
	/**
	 * @throws \backend\exceptions\CriticalException
	 */
	public function getLastBackup(): int;
}