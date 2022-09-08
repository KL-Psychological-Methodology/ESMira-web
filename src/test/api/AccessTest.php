<?php

namespace test\api;

use backend\JsonOutput;
use backend\subStores\ResponsesStore;
use backend\subStores\StudyStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseApiTestSetup;

require_once __DIR__ .'/../../backend/autoload.php';

class AccessTest extends BaseApiTestSetup {
	private $studyId = 123;
	private $lockedStudyId = 991;
	private $pageName = 'pageName';
	
	protected function tearDown(): void {
		parent::tearDown();
		$this->isInit = true;
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$responsesStore = $this->createMock(ResponsesStore::class);
		$responsesStore
			->method('saveWebAccessDataSet')
			->with($this->studyId, $this->anything(), $this->pageName, '', '')
			->willReturn(true);
		$this->createStoreMock('getResponsesStore', $responsesStore, $observer);
		
		
		$studyStore = $this->createStub(StudyStore::class);
		$studyStore->method('isLocked')
			->willReturnCallback(function(int $studyId): bool {
				return $studyId == $this->lockedStudyId;
			});
		$this->createStoreMock('getStudyStore', $studyStore, $observer);
		
		return $observer;
	}
	
	function test() {
		$this->setPost([
			'study_id' => $this->studyId,
			'page_name' => $this->pageName
		]);
		require DIR_BASE .'/api/access.php';
		$this->expectOutputString(JsonOutput::successObj());
	}
	
	
	function test_with_locked_study() {
		$this->setPost([
			'study_id' => $this->lockedStudyId,
			'page_name' => $this->pageName
		]);
		require DIR_BASE .'/api/access.php';
		$this->expectOutputString(JsonOutput::error('This study is locked'));
	}
	
	function test_without_init() {
		$this->assertIsInit('access');
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForApi(
			[
				'study_id' => $this->studyId,
				'page_name' => $this->pageName
			],
			'access'
		);
	}
}