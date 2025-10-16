<?php

declare(strict_types=1);

namespace backend\subStores;

use backend\exceptions\CriticalException;
use backend\ResponsesIndex;

interface StudyStore extends BaseStudyStore
{
	public function getStudyLastChanged(int $studyId): int;

	public function getStudyParticipants(int $studyId): array;

	public function questionnaireExists(int $studyId, int $questionnaireId): bool;
	/**
	 * @throws CriticalException
	 */
	public function getEventIndex(int $studyId): ResponsesIndex;
	/**
	 * @throws CriticalException
	 */
	public function getQuestionnaireIndex(int $studyId, int $questionnaireId): ResponsesIndex;

	/**
	 * @throws CriticalException
	 */
	public function backupStudy(int $studyId);

	/**
	 * @throws CriticalException
	 */
	public function emptyStudy(int $studyId, array $questionnaireKeys);

	public function deleteBackups(int $studyId);

	/**
	 * @throws CriticalException
	 */
	public function markStudyAsUpdated(int $studyId);
}