<?php

namespace backend\fileSystem\subStores;

use backend\subStores\StatisticsStoreWriter;
use stdClass;

class StatisticsStoreWriterFS implements StatisticsStoreWriter {
	/**
	 * @var stdClass
	 */
	private $statistics;
	
	
	public function __construct(stdClass $statistics) {
		$this->statistics = $statistics;
	}
	
	private function addEventToStatistics(stdClass &$current, string $event, int $count) {
		if(!isset($current->{$event}))
			$current->{$event} = $count;
		else
			$current->{$event} += $count;
		
		$this->statistics->week->{$event}[date('w')] += $count;
		$this->statistics->total->{$event} += $count;
	}
	
	private static function getFirstKeyFromObj(stdClass $obj) {
		foreach($obj as $key => $value) {
			return $key;
		}
		return null;
	}
	
	public function getStatisticsObj(): stdClass {
		return $this->statistics;
	}
	
	
	
	public function incrementStudies() {
		$this->statistics->total->studies += 1;
	}
	public function decrementStudies() {
		$this->statistics->total->studies -= 1;
	}
	public function incrementUser(int $num = 1) {
		$this->statistics->total->users += $num;
	}
	public function incrementAndroid(int $num = 1) {
		$this->statistics->total->android += $num;
	}
	public function incrementIos(int $num = 1) {
		$this->statistics->total->ios += $num;
	}
	public function incrementWeb(int $num = 1) {
		$this->statistics->total->web += $num;
	}
	
	public function addDataToDay(int $oldestAllowedEntryTime, int $startOfDay, string $appType, string $appVersion, int $questionnaireEvents, int $joinEvents) {
		$versionString = $appType .' ' .$appVersion;
		$box = $this->statistics->days;
		if(!isset($box->{$startOfDay})) {
			$box->{$startOfDay} = new stdClass();
			
			while(($key = self::getFirstKeyFromObj($box)) < $oldestAllowedEntryTime && $key != null) {
				unset($box->{$key});
			}
		}
		
		$current = &$box->{$startOfDay};
		if(!isset($current->appVersion))
			$current->appVersion = (object) [$versionString => 1];
		else if(!isset($current->appVersion->{$versionString}))
			$current->appVersion->{$versionString} = 1;
		else
			$current->appVersion->{$versionString} += 1;
		
		
		
		if($questionnaireEvents != 0)
			self::addEventToStatistics($current, 'questionnaire', $questionnaireEvents);
		
		if($joinEvents != 0)
			self::addEventToStatistics($current, 'joined', $joinEvents);
	}
}