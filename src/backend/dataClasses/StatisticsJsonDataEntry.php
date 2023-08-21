<?php

namespace backend\dataClasses;

use stdClass;

class StatisticsJsonDataEntry extends StatisticsJsonEntry {
	/**
	 * @var stdClass
	 */
	public $data;
	
	public function __construct(StudyStatisticsEntry $observedEntry, int $entryCount = 0) {
		parent::__construct($observedEntry, $entryCount);
		$this->data = new stdClass();
	}
}