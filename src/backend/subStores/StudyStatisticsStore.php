<?php

namespace backend\subStores;

use backend\CriticalError;
use backend\dataClasses\StatisticsJsonEntry;
use stdClass;

interface StudyStatisticsStore {
	function addEntry(string $key, StatisticsJsonEntry $jsonEntry);
	/**
	 * @throws CriticalError
	 */
	public function getStatistics(): stdClass;
	/**
	 * @throws CriticalError
	 */
	public function saveChanges();
}