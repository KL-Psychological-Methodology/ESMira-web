<?php

namespace test\backend\admin\features\writePermission;

use backend\admin\features\writePermission\FreezeStudy;
use backend\subStores\StudyStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseWritePermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class FreezeStudyTest extends BaseWritePermissionTestSetup {
	private $isLockedReturn = false;
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		
		$store = $this->createDataMock(StudyStore::class, 'lockStudy');
		$store->method('isLocked')
			->willReturnCallback(function(int $studyId) {
				$this->assertEquals($this->studyId, $studyId);
				return $this->isLockedReturn;
			});
		$this->createStoreMock(
			'getStudyStore',
			$store,
			$observer
		);
		
		return $observer;
	}
	
	function test_unfreeze() {
		$obj = new FreezeStudy();
		
		$this->assertEquals([$this->isLockedReturn], $obj->exec());
		$this->assertDataMock('lockStudy', [$this->studyId, false]);
	}
	function test_freeze() {
		$this->setGet(['frozen' => true]);
		$this->isLockedReturn = true;
		$obj = new FreezeStudy();
		
		$this->assertEquals([$this->isLockedReturn], $obj->exec());
		$this->assertDataMock('lockStudy', [$this->studyId, true]);
	}
}