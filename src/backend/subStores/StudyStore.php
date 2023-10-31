<?php
declare(strict_types=1);

namespace backend\subStores;

use backend\dataClasses\RewardCodeData;
use backend\exceptions\CriticalException;
use backend\exceptions\NoRewardCodeException;
use backend\ResponsesIndex;
use stdClass;

interface StudyStore {
	public function studyExists(int $studyId): bool;
	public function isLocked(int $studyId): bool;
	public function lockStudy(int $studyId, bool $lock=true);
	
	public function getStudyLastChanged(int $studyId): int;
	
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
	public function getStudyParticipants(int $studyId): array;
	
	/**
	 * @throws CriticalException
	 */
	public function getEventIndex(int $studyId): ResponsesIndex;
	/**
	 * @throws CriticalException
	 */
	public function getQuestionnaireIndex(int $studyId, int $questionnaireId): ResponsesIndex;
	
	public function questionnaireExists(int $studyId, int $questionnaireId): bool;
	
	/**
	 * @throws CriticalException
	 */
	public function saveStudy(stdClass $studyCollection, array $questionnaireKeys);
	
	/**
	 * @throws CriticalException
	 */
	public function backupStudy(int $studyId);
	
	/**
	 * @throws CriticalException
	 */
	public function emptyStudy(int $studyId, array $questionnaireKeys);
	
	/**
	 * @throws CriticalException
	 */
	public function markStudyAsUpdated(int $studyId);
	
	/**
	 * @throws CriticalException
	 */
	public function delete(int $studyId);
}