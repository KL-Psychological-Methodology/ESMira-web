<?php

namespace backend\dataClasses;

use stdClass;

class StatisticsJsonEntry {
	/**
	 * @var int
	 */
	public $storageType;
	/**
	 * @var stdClass
	 */
	public $data;
	/**
	 * @var int
	 */
	public $timeInterval;
	/**
	 * @var int
	 */
	public $entryCount;
	
	public function __construct(StudyStatisticsEntry $observedEntry, int $entryCount = 0) {
		$this->storageType = $observedEntry->storageType;
		$this->data = new stdClass();
		$this->timeInterval = $observedEntry->timeInterval;
		$this->entryCount = $entryCount;
	}
}