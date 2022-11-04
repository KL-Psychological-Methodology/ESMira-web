<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;

interface StudyMetadataStore {
	/**
	 * @throws CriticalException
	 */
	public function updateMetadata($study);
	/**
	 * @throws CriticalException
	 */
	public function getVersion(): int;
	/**
	 * @throws CriticalException
	 */
	public function isPublished(): bool;
	/**
	 * @throws CriticalException
	 */
	public function getAccessKeys(): array;
	/**
	 * @throws CriticalException
	 */
	public function getLastBackup(): int;
}