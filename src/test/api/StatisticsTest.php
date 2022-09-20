<?php

namespace test\api;

use backend\exceptions\CriticalException;
use backend\JsonOutput;
use backend\subStores\StudyMetadataStore;
use backend\subStores\StudyStatisticsStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseApiTestSetup;

require_once __DIR__ .'/../../backend/autoload.php';

class StatisticsTest extends BaseApiTestSetup {
	private $statisticsContent;
	private $accessKeys = [];
	private $studyMetadataStoreError = false;
	private $studyStatisticsStoreError = false;
	
	public function setUp(): void {
		parent::setUp();
		$this->statisticsContent = (object) ['content'];
		$this->accessKeys = [];
		$this->studyMetadataStoreError = false;
		$this->studyStatisticsStoreError = false;
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$statisticsStore = $this->createStub(StudyMetadataStore::class);
		$statisticsStore->method('getAccessKeys')
			->willReturnCallback(function() {
				if($this->studyMetadataStoreError)
					throw new CriticalException('StudyMetadataStore error');
				return $this->accessKeys;
			});
		$this->createStoreMock('getStudyMetadataStore', $statisticsStore, $observer);
		
		
		$statisticsStore = $this->createStub(StudyStatisticsStore::class);
		$statisticsStore->method('getStatistics')
			->willReturnCallback(function() {
				if($this->studyStatisticsStoreError)
					throw new CriticalException('StudyStatisticsStore error');
				return $this->statisticsContent;
			});
		$this->createStoreMock('getStudyStatisticsStore', $statisticsStore, $observer);
		
		return $observer;
	}
	
	function test() {
		$this->setGet(['id' => 123]);
		require DIR_BASE .'/api/statistics.php';
		$this->expectOutputString(JsonOutput::successObj($this->statisticsContent));
	}
	
	function test_with_error_in_studyStatisticsStore() {
		$this->setGet(['id' => 123]);
		$this->studyStatisticsStoreError = true;
		require DIR_BASE .'/api/statistics.php';
		$this->expectOutputString(JsonOutput::error('StudyStatisticsStore error'));
	}
	
	function test_with_error_in_studyMetadataStore() {
		$this->setGet(['id' => 123]);
		$this->studyMetadataStoreError = true;
		require DIR_BASE .'/api/statistics.php';
		$this->expectOutputString(JsonOutput::error('StudyMetadataStore error'));
	}
	
	function test_with_wrong_accessKey() {
		$this->setGet(['id' => 123, 'access_key' => 'keyWrong']);
		$this->accessKeys = ['key'];
		require DIR_BASE .'/api/statistics.php';
		$this->expectOutputString(JsonOutput::error('Wrong accessKey: keyWrong'));
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForApi(['id' => 123], 'statistics', true);
	}
	
	function test_without_init() {
		$this->assertIsInit('statistics');
	}
}