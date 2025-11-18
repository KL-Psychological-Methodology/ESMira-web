<?php

namespace backend\dataClasses;


/**
 * The format saved by StudyStatisticsMetadataStoreFS to PathFS::fileStudyStatisticsMetadata.
 * In the metadata each variable has an array of StudyStatisticsEntry (each array entry is one statistic variable)
 * Will be used in json_decode()
 */
class StudyStatisticsEntry {
	/**
	 * @var array
	 */
	public $conditions;
	/**
	 * @var int
	 */
	public $conditionType;
	/**
	 * @var int
	 */
	public $storageType;
	/**
	 * @var int
	 */
	public $timeInterval;
	
	public function __construct(array $conditions, int $conditionType, int $storageType, int $timeInterval) {
		$this->conditions = $conditions;
		$this->conditionType = $conditionType;
		$this->storageType = $storageType;
		$this->timeInterval = $timeInterval;
	}
}