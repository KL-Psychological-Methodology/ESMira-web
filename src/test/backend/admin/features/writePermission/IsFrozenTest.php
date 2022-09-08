<?php

namespace test\backend\admin\features\writePermission;

use backend\admin\features\writePermission\IsFrozen;
use backend\subStores\StudyStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseWritePermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class IsFrozenTest extends BaseWritePermissionTestSetup {
	private $isLockedReturn = false;
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getStudyStore',
			$this->createDataMock(StudyStore::class, 'isLocked', $this->isLockedReturn),
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$obj = new IsFrozen();
		
		$this->assertEquals([$this->isLockedReturn], $obj->exec());
		$this->assertDataMock('isLocked', [$this->studyId]);
	}
}