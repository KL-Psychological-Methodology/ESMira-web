<?php

namespace test\api;

use backend\CreateDataSet;
use backend\JsonOutput;
use backend\Main;
use backend\ResponsesIndex;
use backend\subStores\StudyStore;
use backend\subStores\UserDataStore;
use PHPUnit\Framework\MockObject\Stub;
use stdClass;
use test\testConfigs\BaseApiTestSetup;

require_once __DIR__ .'/../../backend/autoload.php';

class DatasetsTest extends BaseApiTestSetup {
	protected function tearDown(): void {
		parent::tearDown();
		$this->isInit = true;
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$responsesStore = $this->createMock(UserDataStore::class);
		$responsesStore
			->method('addDataSetForSaving')
			->willReturn(true);

		$this->createStoreMock('getUserDataStore', $responsesStore, $observer);
		
		
		$studyStore = $this->createMock(StudyStore::class);
		$studyStore
			->method('getEventIndex')
			->willReturn(new ResponsesIndex([]));

		$this->createStoreMock('getStudyStore', $studyStore, $observer);
		
		return $observer;
	}
	
	function test() {
		Main::$defaultPostInput = json_encode([
			'userId' => 'userId',
			'appVersion' => 'appVersion',
			'appType' => 'appType',
			'dataset' => [
				(object) [
					'dataSetId' => '123456',
					'studyId' => 123,
					'eventType' => CreateDataSet::DATASET_TYPE_JOINED,
					'responses' => (object) []
				]
			],
			'serverVersion' => Main::ACCEPTED_SERVER_VERSION
		]);
		$this->expectOutputString(JsonOutput::successObj(['states' => [], 'tokens' => new stdClass()]));
		require DIR_BASE .'/api/datasets.php';
	}
	
	function test_with_error() {
		$studyId = 123;
		
		Main::$defaultPostInput = json_encode([]);
		$this->expectOutputString(JsonOutput::error('Unexpected data format'));
		require DIR_BASE .'/api/datasets.php';
	}
	
	function test_without_init() {
		$this->assertIsInit('datasets');
	}
	
	function test_with_faulty_data() {
		Main::$defaultPostInput = '';
		$this->expectOutputString(JsonOutput::error('Unexpected data format'));
		require DIR_BASE .'/api/datasets.php';
	}
}