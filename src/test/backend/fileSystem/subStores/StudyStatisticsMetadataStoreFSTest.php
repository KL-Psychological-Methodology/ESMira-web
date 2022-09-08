<?php

namespace test\backend\fileSystem\subStores;

use backend\Configs;
use backend\CreateDataSet;
use backend\dataClasses\StudyStatisticsMetadataEntry;
use backend\dataClasses\StudyStatisticsEntry;
use backend\fileSystem\subStores\StudyStatisticsMetadataStoreFS;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ . '/../../../../backend/autoload.php';

class StudyStatisticsMetadataStoreFSTest extends BaseDataFolderTestSetup {
	private $studyId = 123;
	function setUp(): void {
		$this->createEmptyStudy($this->studyId);
	}
	function tearDown(): void {
		Configs::getDataStore()->getStudyStore()->delete($this->studyId);
	}
	
	function test() {
		$conditionType = CreateDataSet::CONDITION_TYPE_AND;
		$storageType = CreateDataSet::STATISTICS_STORAGE_TYPE_FREQ_DISTR;
		$timeInterval = 5;
		$statisticsKey1 = 'key1';
		$statisticsKey2 = 'key2';
		$conditions = [
			(object) [
				'key' => $statisticsKey1,
				'value' => 'value1',
				'operator' => CreateDataSet::CONDITION_OPERATOR_UNEQUAL,
			]
		];
		$observedVariableJsonEntry = new StudyStatisticsEntry($conditions, $conditionType, $storageType, $timeInterval);
		$store = New StudyStatisticsMetadataStoreFS($this->studyId);
		$store->addMetadataEntry($statisticsKey1, $observedVariableJsonEntry);
		$store->addMetadataEntry($statisticsKey2, $observedVariableJsonEntry);
		$store->saveChanges();
		
		$collection = $store->loadMetadataCollection();
		
		
		$this->assertArrayHasKey($statisticsKey2, $collection);
		$element = $collection[$statisticsKey1][0];
		$this->assertEquals($conditions, $element->conditions);
		$this->assertEquals($conditionType, $element->conditionType);
		$this->assertEquals($storageType, $element->storageType);
		$this->assertEquals($timeInterval, $element->timeInterval);
	}
}