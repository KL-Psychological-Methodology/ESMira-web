<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;
use stdClass;

interface StudyMetadataStore {
	/**
	 * @throws CriticalException
	 */
	public function updateMetadata(stdClass $study);
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
	public function hasQuestionnaires(): bool;
	/**
	 * @throws CriticalException
	 */
	public function getAccessKeys(): array;
	/**
	 * @throws CriticalException
	 */
	public function getTitle(): string;
	/**
	 * @throws CriticalException
	 */
	public function getLastSavedBy(): string;
	/**
	 * @throws CriticalException
	 */
	public function getLastSavedAt(): int;
	/**
	 * @throws CriticalException
	 */
	public function getOwner(): string;
	/**
	 * @throws CriticalException
	 */
	public function getCreatedTimestamp(): int;
	/**
	 * @throws CriticalException
	 */
	public function getStudyTag(): string;
}