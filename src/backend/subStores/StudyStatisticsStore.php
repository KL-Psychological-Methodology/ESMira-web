<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;
use backend\dataClasses\StatisticsJsonEntry;
use stdClass;

interface StudyStatisticsStore {
	function addEntry(string $key, StatisticsJsonEntry $jsonEntry);
	/**
	 * @throws \backend\exceptions\CriticalException
	 */
	public function getStatistics(): stdClass;
	/**
	 * @throws \backend\exceptions\CriticalException
	 */
	public function saveChanges();
}