<?php
declare(strict_types=1);

namespace backend;

use backend\exceptions\CriticalException;

class DataSetCache {
	/**
	 * @var DataSetCacheContainer[]
	 */
	private $statisticsCache = [];
	/**
	 * @var DataSetCacheContainer[]
	 */
	private $eventCache = [];
	/**
	 * @var DataSetCacheContainer[]
	 */
	private $questionnaireCache = [];
	
	/**
	 * @var ResponsesIndex[]
	 */
	private $eventIndexCache = [];
	/**
	 * @var ResponsesIndex[]
	 */
	private $questionnaireIndexCache = [];
	
	private $fileCache = [];
	
	private function addToCache(array &$cache, int $key, int $datasetId, /*mixed*/ $data) {
		if(isset($cache[$key]))
			$cache[$key]->add($datasetId, $data);
		else
			$cache[$key] = new DataSetCacheContainer($datasetId, $data);
	}
	
	public function addToFileCache(int $studyId, string $internalpath, int $identifier, int $datasetId) {
		$this->fileCache[$datasetId] = new DataSetCacheFileEntry($studyId, $internalpath, $identifier);
	}
	
	public function addToStatisticsCache(int $studyId, int $datasetId, DataSetCacheStatisticsEntry $data) {
		$this->addToCache($this->statisticsCache, $studyId, $datasetId, $data);
	}
	public function addToEventCache(int $studyId, int $datasetId, array $data) {
		$this->addToCache($this->eventCache, $studyId, $datasetId, $data);
	}
	public function addToQuestionnaireCache(int $studyId, int $questionnaireId, int $datasetId, array $data) {
		if(!isset($this->questionnaireCache[$studyId]))
			$this->questionnaireCache[$studyId] = [];
		
		$this->addToCache($this->questionnaireCache[$studyId], $questionnaireId, $datasetId, $data);
	}
	
	/**
	 * @throws CriticalException
	 */
	public function getEventIndex(int $studyId): ResponsesIndex {
		return $this->eventIndexCache[$studyId]
			?? ($this->eventIndexCache[$studyId] = Configs::getDataStore()->getStudyStore()->getEventIndex($studyId));
	}
	
	/**
	 * @throws CriticalException
	 */
	public function getQuestionnaireIndex(int $studyId, int $questionnaireId): ResponsesIndex {
		return $this->questionnaireIndexCache[$studyId .$questionnaireId]
			?? ($this->questionnaireIndexCache[$studyId .$questionnaireId] = Configs::getDataStore()->getStudyStore()->getQuestionnaireIndex($studyId, $questionnaireId));
	}
	
	public function getStatisticsCache(): array {
		return $this->statisticsCache;
	}
	public function getEventCache(): array {
		return $this->eventCache;
	}
	public function getQuestionnaireCache(): array {
		return $this->questionnaireCache;
	}
	
	function getFileCache(): array {
		return $this->fileCache;
	}
}