<?php

namespace backend\subStores;

use stdClass;

interface BaseStudyStore
{
	public function studyExists(int $studyId): bool;
	public function isLocked(int $studyId): bool;
	public function lockStudy(int $studyId, bool $lock = true);

	public function getStudyIdList(): array;

	public function getDirectorySizeOfStudies(): array;
	/**
	 * @throws CriticalException
	 */
	public function getStudyLangConfigAsJson(int $studyId, string $lang);
	/**
	 * @throws CriticalException
	 */
	public function getStudyConfigAsJson(int $studyId): string;
	/**
	 * @throws CriticalException
	 */
	public function getStudyLangConfig(int $studyId, string $lang): stdClass;
	/**
	 * @throws CriticalException
	 */
	public function getStudyConfig(int $studyId): stdClass;
	public function getAllLangConfigsAsJson(int $studyId): string;
	/**
	 * @throws CriticalException
	 */
	public function saveStudy(stdClass $studyCollection, array $questionnaireKeys);
	/**
	 * @throws CriticalException
	 */
	public function delete(int $studyId);
}
