<?php

namespace backend\dataClasses;

use stdClass;

class StatisticsJsonTimedEntry extends StatisticsJsonEntry {
	/**
	 * @var stdClass
	 */
	public $data;
	/**
	 * @var int
	 */
	public $timeInterval;
	
	public function __construct(StudyStatisticsEntry $observedEntry, int $entryCount = 0) {
		parent::__construct($observedEntry, $entryCount);
		$this->data = new stdClass();
		$this->timeInterval = $observedEntry->timeInterval;
	}
}