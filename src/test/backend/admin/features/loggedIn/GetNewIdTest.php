<?php

namespace test\backend\admin\features\loggedIn;

use backend\admin\features\writePermission\GetNewId;
use backend\subStores\StudyAccessIndexStore;
use backend\subStores\StudyStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseWritePermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class GetNewIdTest extends BaseWritePermissionTestSetup {
	private $callCounterStudyExists = 0;
	private $callCounterGetStudyIdForQuestionnaireId = 0;
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		
		$studyAccessIndexStore = $this->createMock(StudyAccessIndexStore::class);
		$studyAccessIndexStore->expects($this->exactly(3))
			->method('getStudyIdForQuestionnaireId')
			->willReturnCallback(function() {
				return (++$this->callCounterGetStudyIdForQuestionnaireId) < 3 ? 123 : -1;
			});
		$this->createStoreMock(
			'getStudyAccessIndexStore',
			$studyAccessIndexStore,
			$observer
		);
		
		$studyStore = $this->createMock(StudyStore::class);
		$studyStore->expects($this->atLeast(3))
			->method('studyExists')
			->willReturnCallback(function() {
				return ++$this->callCounterStudyExists < 3;
			});
		
		$this->createStoreMock('getStudyStore', $studyStore, $observer);
		
		return $observer;
	}
	
	function test() {
		$obj = new GetNewId();
		$obj->exec();
	}
}