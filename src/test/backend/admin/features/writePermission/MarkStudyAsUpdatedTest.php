<?php

namespace test\backend\admin\features\writePermission;

use backend\admin\features\writePermission\MarkStudyAsUpdated;
use backend\subStores\StudyStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseWritePermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class MarkStudyAsUpdatedTest extends BaseWritePermissionTestSetup {
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getStudyStore',
			$this->createDataMock(StudyStore::class, 'markStudyAsUpdated'),
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$obj = new MarkStudyAsUpdated();
		
		$time = time();
		$this->assertGreaterThanOrEqual($time, $obj->exec());
		$this->assertDataMock('markStudyAsUpdated', [$this->studyId]);
	}
}