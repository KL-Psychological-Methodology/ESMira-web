<?php

namespace backend\dataClasses;

use backend\CreateDataSet;

class StatisticsJsonEntry {
	/**
	 * @var int
	 */
	public $storageType;
	/**
	 * @var int
	 */
	public $entryCount;
	
	public function __construct(StudyStatisticsEntry $observedEntry, int $entryCount = 0) {
		$this->storageType = $observedEntry->storageType;
		$this->entryCount = $entryCount;
	}
	
	public static function createNew(StudyStatisticsEntry $observedEntry, int $entryCount = 0) {
		switch($observedEntry->storageType) {
			case CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED:
				return new StatisticsJsonTimedEntry($observedEntry, $entryCount);
			case CreateDataSet::STATISTICS_STORAGE_TYPE_FREQ_DISTR:
			case CreateDataSet::STATISTICS_STORAGE_TYPE_PER_DATA:
				return new StatisticsJsonDataEntry($observedEntry, $entryCount);
			default:
				return new StatisticsJsonEntry($observedEntry, $entryCount);
		}
	}
}