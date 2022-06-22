<?php
declare(strict_types=1);

namespace backend\subStores;

use backend\CriticalError;
use backend\ResponsesIndex;
use stdClass;

interface StudyStore {
	public function studyExists(int $studyId): bool;
	public function isLocked(int $studyId): bool;
	public function lockStudy(int $studyId, bool $lock=true);
	
	public function getStudyLastChanged(int $studyId): int;
	
	public function getStudyIdList(): array;
	/**
	 * @throws CriticalError
	 */
	public function getStudyLangConfigAsJson(int $studyId, string $lang);
	/**
	 * @throws CriticalError
	 */
	public function getStudyConfigAsJson(int $studyId): string;
	/**
	 * @throws CriticalError
	 */
	public function getStudyLangConfig(int $studyId, string $lang): stdClass;
	/**
	 * @throws CriticalError
	 */
	public function getStudyConfig(int $studyId): stdClass;
	public function getAllLangConfigsAsJson(int $studyId): string;
	public function getStudyParticipants(int $studyId): array;
	
	/**
	 * @throws CriticalError
	 */
	public function getEventIndex(int $studyId): ResponsesIndex;
	/**
	 * @throws CriticalError
	 */
	public function getQuestionnaireIndex(int $studyId, int $questionnaireId): ResponsesIndex;
	
	public function questionnaireExists(int $studyId, int $questionnaireId): bool;
	
	/**
	 * @throws CriticalError
	 */
	public function saveStudy(stdClass $studyCollection, array $questionnaireKeys);
	
	/**
	 * @throws CriticalError
	 */
	public function backupStudy(int $studyId);
	
	/**
	 * @throws CriticalError
	 */
	public function emptyStudy(int $studyId, array $questionnaireKeys);
	
	/**
	 * @throws CriticalError
	 */
	public function markStudyAsUpdated(int $studyId);
	
	/**
	 * @throws CriticalError
	 */
	public function delete(int $studyId);
}