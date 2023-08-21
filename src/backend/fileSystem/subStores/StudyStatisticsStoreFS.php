<?php

namespace backend\fileSystem\subStores;

use ArrayIterator;
use backend\dataClasses\StatisticsJsonEntry;
use backend\Main;
use backend\Configs;
use backend\CreateDataSet;
use backend\exceptions\CriticalException;
use backend\DataSetCacheStatisticsEntry;
use backend\fileSystem\PathsFS;
use backend\fileSystem\loader\StatisticsNewDataSetEntryLoader;
use backend\fileSystem\loader\StudyStatisticsLoader;
use backend\subStores\StudyStatisticsStore;
use stdClass;

const ONE_DAY = 86400; //in seconds: 60*60*24
class StudyStatisticsStoreFS implements StudyStatisticsStore {
	/**
	 * @var stdClass
	 */
	private $json;
	/**
	 * @var int
	 */
	private $studyId;
	
	public function __construct(int $studyId) {
		$this->json = new stdClass();
		$this->studyId = $studyId;
	}
	
	public function addEntry(string $key, StatisticsJsonEntry $jsonEntry) {
		if(!isset($this->json->{$key}))
			$this->json->{$key} = [];
		
		$this->json->{$key}[] = $jsonEntry;
	}
	
	private function calcTimestamp($timestamp, $timeInterval) {
		return floor($timestamp / $timeInterval) * $timeInterval;
	}
	
	private function increaseTimeInterval(int $timeInterval, stdClass $currentJson) {
		$data = $currentJson->data;
		$timeInterval *= 2;
		if($timeInterval > ONE_DAY)
			$timeInterval = ONE_DAY;
		
		$currentJson->timeInterval = $timeInterval;
		
		$newData = new stdClass();
		$newEntryCount = 0;
		
		foreach($data as $key => $set) {
			$t = $this->calcTimestamp($key, $timeInterval);
			if(isset($newData->{$t})) {
				$newData->{$t}->sum += $set->sum;
				$newData->{$t}->count += $set->count;
			}
			else {
				$newData->{$t} = $set;
				++$newEntryCount;
			}
		}
		$currentJson->entryCount = $newEntryCount;
		$currentJson->data = $newData;
	}
	private function handleTimedStorageData(stdClass $statisticsJson, DataSetCacheStatisticsEntry $statisticsEntry) {
		$currentJson = &$statisticsJson->{$statisticsEntry->key}[$statisticsEntry->index];
		$hasAnswer = $statisticsEntry->answer != '';
		$data = &$currentJson->data;
		$timeInterval = $currentJson->timeInterval ?? ONE_DAY;
		$answerTimestamp = $this->calcTimestamp($statisticsEntry->timestamp/1000, $timeInterval);
		
		if(isset($data->{$answerTimestamp})) {
			$entry = $data->{$answerTimestamp};
			if($hasAnswer)
				$entry->sum += floatval($statisticsEntry->answer);
			++$entry->count;
		}
		else {
			++$currentJson->entryCount;
			$data->{$answerTimestamp} = (object)[
				'sum' => $hasAnswer ? floatval($statisticsEntry->answer) : 0,
				'count' => 1
			];
			
			if($currentJson->entryCount > Configs::get('statistics_timed_storage_max_entries') && $currentJson->timeInterval != ONE_DAY) {
				Main::report("Warning: The statistics in the study with id $this->studyId has too many entries saved for variable $statisticsEntry->key [$statisticsEntry->index]. timeInterval was increased to $timeInterval\n\nIf this warning happens continuously, consider increasing smallest_timed_distance or statistics_timed_storage_max_entries in backend/config/configs.php.");
				$this->increaseTimeInterval($timeInterval, $currentJson);
			}
		}
	}
	private function handleFreqDistributionStorageData(stdClass $statisticsJson, DataSetCacheStatisticsEntry $statisticsEntry) {
		$currentJson = &$statisticsJson->{$statisticsEntry->key}[$statisticsEntry->index];
		$hasAnswer = $statisticsEntry->answer != '';
		$data = &$currentJson->data;
		
		if($hasAnswer) {
			if(isset($data->{$statisticsEntry->answer}))
				++$data->{$statisticsEntry->answer};
			else {
				$data->{$statisticsEntry->answer} = 1;
				++$currentJson->entryCount;
			}
		}
	}
	private function handlePerDataStorageData(stdClass $statisticsJson, DataSetCacheStatisticsEntry $statisticsEntry) {
		//this is always used in connection with other variables. In theory, it is possible that variables have a different amount of entries.
		// This can become a problem when we have to delete entries and only delete them in one variable
		// (because the first entry in this variable is then not guaranteed to point to the same value at the other variable anymore).
		// So we need to make sure that keys stay the same after deleting entries -> we cant use a simple array but use an object instead
		$currentJson = &$statisticsJson->{$statisticsEntry->key}[$statisticsEntry->index];
		$data = &$currentJson->data;
		
		if($currentJson->entryCount == 0) {
			$data->{0} = floatval($statisticsEntry->answer);
			$currentJson->entryCount = 1;
		}
		else {
			$iterator = new ArrayIterator($data);
			$firstKey = $iterator->key();
			$newKey = $firstKey + $currentJson->entryCount;
			$data->{$newKey} = floatval($statisticsEntry->answer);
			++$currentJson->entryCount;
			
			$maxEntries = Configs::get('statistics_per_data_storage_max_entries');
			while($currentJson->entryCount > $maxEntries) {
				$iterator->rewind();
				unset($data->{$iterator->key()});
				--$currentJson->entryCount;
			}
		}
	}
	
	public function getStatistics(): stdClass {
		//this also locks the statistics file. Thus making sure that getStatistics() is only run sequentially:
		$statisticsJson = StudyStatisticsLoader::importFile($this->studyId, true);
		
		$pathNewData = PathsFS::fileStatisticsNewData($this->studyId);
		$pathNewDataCopy = $pathNewData .'_copy';
		
		if(!file_exists($pathNewData)) {
			StudyStatisticsLoader::close();
			return $statisticsJson;
		}
		
		if(!rename($pathNewData, $pathNewDataCopy)) {//we move the current file to make sure that, if new data is created while we process, it will just be ignored
			Main::report("Could not rename \"$pathNewData\" into \"$pathNewDataCopy\" for study id $this->studyId. Processing new statistics is canceled!");
			StudyStatisticsLoader::close();
			return $statisticsJson;
		}
		
		$handleNewData = fopen($pathNewDataCopy, 'r');
		
		for(
			$count = 1; $maxCount = Configs::get('statistics_cache_max_processed_entries'),
			($line = fgets($handleNewData)) !== false;
			++$count
		) {
			$trimLine = trim($line);
			if(empty($trimLine))
				continue;
			$statisticsEntry = StatisticsNewDataSetEntryLoader::import($trimLine);
			
			if(!isset($statisticsJson->{$statisticsEntry->key}[$statisticsEntry->index])) {
				Main::report("Error when updating statistics for study $this->studyId. Key \"$statisticsEntry->key\" does not exist in:\n" .print_r($statisticsJson, true));
				continue; //this should never happen!
			}
			
			$currentJson = &$statisticsJson->{$statisticsEntry->key}[$statisticsEntry->index];
			
			switch($currentJson->storageType) {
				case CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED:
					$this->handleTimedStorageData($statisticsJson, $statisticsEntry);
					break;
				case CreateDataSet::STATISTICS_STORAGE_TYPE_FREQ_DISTR:
					$this->handleFreqDistributionStorageData($statisticsJson, $statisticsEntry);
					break;
				case CreateDataSet::STATISTICS_STORAGE_TYPE_PER_DATA:
					$this->handlePerDataStorageData($statisticsJson, $statisticsEntry);
					break;
				default:
					Main::report("Unknown storageType (\"$currentJson->storageType\") in study $this->studyId");
					break;
			}
			
			if($count >= $maxCount) {//there is too much data to process at once. We save the rest back again and process it next time
				Main::report("Warning: The study with id $this->studyId had too much data for new statistics to process at once.\n\nIf this warning happens continuously, consider increasing statistics_cache_max_processed_entries in backend/config/configs.php");
				
				//we create the original copy file again and fill it with the missing lines
				//(or, if it was created in the meantime, it will be added to the end of the original):
				$handleCopy = fopen($pathNewData, 'a');
				stream_copy_to_stream($handleNewData, $handleCopy);
				break;
			}
		}
		
		fclose($handleNewData);
		if(!unlink($pathNewDataCopy))
			Main::report("Could not remove \"$pathNewDataCopy\" for study id $this->studyId! This will most likely lead to problems when we want to process the next batch for statistics.");
		
		StudyStatisticsLoader::exportFile($this->studyId, $statisticsJson);
		
		return $statisticsJson;
	}
	
	public function saveChanges() {
		StudyStatisticsLoader::exportFile($this->studyId, $this->json);
	}
}