<?php
declare(strict_types=1);

namespace test\backend\fileSystem\subStores;

use backend\Configs;
use backend\CreateDataSet;
use backend\dataClasses\StatisticsJsonEntry;
use backend\dataClasses\StudyStatisticsMetadataEntry;
use backend\dataClasses\StudyStatisticsEntry;
use backend\DataSetCacheStatisticsEntry;
use backend\fileSystem\loader\StatisticsNewDataSetEntryLoader;
use backend\fileSystem\PathsFS;
use backend\fileSystem\subStores\StudyStatisticsMetadataStoreFS;
use backend\fileSystem\subStores\StudyStatisticsStoreFS;
use stdClass;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ . '/../../../../backend/autoload.php';


class StudyStatisticsStoreFSTest extends BaseDataFolderTestSetup {
	private $studyId = 123;
	private $statisticsKey = 'key1';
	
	function setUp(): void {
		parent::setUp();
		Configs::injectConfig('configs.dataFolder.injected.php');
		$this->createEmptyStudy($this->studyId);
	}
	function tearDown(): void {
		parent::tearDown();
		Configs::getDataStore()->getStudyStore()->delete($this->studyId);
		Configs::resetAll();
	}
	
	private function addStatistics(int $timeInterval, int $storageType) {
		$conditionType = CreateDataSet::CONDITION_TYPE_AND;
		$conditions = [
			(object) [
				'key' => $this->statisticsKey,
				'value' => 'value1',
				'operator' => CreateDataSet::CONDITION_OPERATOR_UNEQUAL,
			]
		];
		$observedVariableJsonEntry = new StudyStatisticsEntry($conditions, $conditionType, $storageType, $timeInterval);
		
		
		$metadata = new StudyStatisticsMetadataStoreFS($this->studyId);
		$metadata->addMetadataEntry($this->statisticsKey, $observedVariableJsonEntry);
		$metadata->saveChanges();
		
		$store = new StudyStatisticsStoreFS($this->studyId);
		$store->addEntry($this->statisticsKey, new StatisticsJsonEntry($observedVariableJsonEntry));
		$store->saveChanges();
	}
	
	private function doAsserts(int $storageType, int $timeInterval, int $entryCount, stdClass $data) {
		$store = new StudyStatisticsStoreFS($this->studyId);
		$index = $store->getStatistics();
		$element = $index->{$this->statisticsKey}[0];
		
		$this->assertEquals($storageType, $element->storageType);
		$this->assertEquals($timeInterval, $element->timeInterval);
		$this->assertEquals($data, $element->data);
		$this->assertEquals($entryCount, $element->entryCount);
	}
	
	function test_save_and_get_empty_statistics() {
		$timeInterval = 5;
		$storageType = CreateDataSet::STATISTICS_STORAGE_TYPE_FREQ_DISTR;
		$this->addStatistics($timeInterval, $storageType);
		
		$this->doAsserts($storageType, $timeInterval, 0, (object) []);
	}
	
	
	function test_save_and_get_timed_statistics_with_existing_cache_and_too_many_times_of_day() {
		Configs::injectConfig('configs.statistics_timed_storage_max_entries.injected.php');
		$timeInterval = 50;
		$storageType = CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED;
		$this->addStatistics($timeInterval, $storageType);
		
		$cacheContent = StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 250000, '2')) ."\n"
			.StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 300000, '5')) ."\n"
			.StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 200000, '8')) ."\n"
			.StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 320000, '7')) ."\n";
		file_put_contents(PathsFS::fileStatisticsNewData($this->studyId), $cacheContent);
		
		$this->doAsserts($storageType, $timeInterval, 3, (object) [
			200 => (object) [
				'sum' => 8,
				'count' => 1
			],
			250 => (object) [
				'sum' => 2,
				'count' => 1
			],
			300 => (object) [
				'sum' => 12,
				'count' => 2
			]
		]);
		
		$cacheContent = StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 360000, '3')) ."\n"
			.StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 250000, '7')) ."\n";
		file_put_contents(PathsFS::fileStatisticsNewData($this->studyId), $cacheContent);
		
		
		$this->doAsserts($storageType, $timeInterval*2, 2, (object) [
			200 => (object) [
				'sum' => 17,
				'count' => 3
			],
			300 => (object) [
				'sum' => 15,
				'count' => 3
			]
		]);
	}
	
	function test_save_and_get_timed_statistics_with_too_many_entries() {
		Configs::injectConfig('configs.statistics_cache_max_processed_entries.injected.php');
		$timeInterval = 50;
		$storageType = CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED;
		$this->addStatistics($timeInterval, $storageType);
		
		$cacheContent = StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 255000, '2')) ."\n"
			.StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 270000, '5')) ."\n"
			.StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 260300, '7')) ."\n"
			
			.StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 280000, '3')) ."\n"
			.StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 250300, '4')) ."\n"
			.StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 250300, '4')) ."\n";
		file_put_contents(PathsFS::fileStatisticsNewData($this->studyId), $cacheContent);
		
		//only the first three entries should be included:
		$this->doAsserts($storageType, $timeInterval, 1, (object) [
			250 => (object) [
				'sum' => 14,
				'count' => 3
			]
		]);
		
		//all entries should be included now:
		$this->doAsserts($storageType, $timeInterval, 1, (object) [
			250 => (object) [
				'sum' => 25,
				'count' => 6
			]
		]);
		
		//nothing should have changed; just making sure everything was deleted properly and no ghost entries are created:
		$this->doAsserts($storageType, $timeInterval, 1, (object) [
			250 => (object) [
				'sum' => 25,
				'count' => 6
			]
		]);
	}
	
	function test_save_and_get_timed_statistics_with_existing_cache() {
		$timeInterval = 60;
		$storageType = CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED;
		$this->addStatistics($timeInterval, $storageType);
		
		$cacheContent = StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 60000, '2')) ."\n"
			."\n\n"
			.StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 120000, '5')) ."\n"
			.StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 60000, '7')) ."\n";
		file_put_contents(PathsFS::fileStatisticsNewData($this->studyId), $cacheContent);
		
		$this->doAsserts($storageType, $timeInterval, 2, (object) [
			60 => (object) [
				'sum' => 9,
				'count' => 2
			],
			120 => (object) [
				'sum' => 5,
				'count' => 1
			]
		]);
	}
	
	function test_save_and_get_freq_distr_statistics_with_existing_cache() {
		$timeInterval = 5;
		$storageType = CreateDataSet::STATISTICS_STORAGE_TYPE_FREQ_DISTR;
		$this->addStatistics($timeInterval, $storageType);
		
		$cacheContent = StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 123456789, 'answer1')) ."\n"
			."\n\n"
			.StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 123456789, 'answer2')) ."\n"
			.StatisticsNewDataSetEntryLoader::export(new DataSetCacheStatisticsEntry($this->statisticsKey, 0, 123456789, 'answer1')) ."\n";
		file_put_contents(PathsFS::fileStatisticsNewData($this->studyId), $cacheContent);
		
		$this->doAsserts($storageType, $timeInterval, 2, (object) [
			'answer1' => 2,
			'answer2' => 1
		]);
	}
}