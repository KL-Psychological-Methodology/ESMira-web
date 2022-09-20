<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;
use stdClass;

interface StudyAccessIndexStore {
	public function getStudyIds(string $key = ''): array;
	public function accessKeyExists(string $key): bool;
	public function getStudyIdForQuestionnaireId(int $internalId): int;
	
	public function add(int $studyId, string $value = '');
	public function addQuestionnaireKeys(stdClass $study);
	public function removeStudy(int $studyId): bool;
	
	/**
	 * @throws CriticalException
	 */
	public function saveChanges();
}