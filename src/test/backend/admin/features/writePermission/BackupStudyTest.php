<?php

namespace test\backend\admin\features\writePermission;

use backend\admin\features\writePermission\BackupStudy;
use backend\subStores\StudyStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseWritePermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class BackupStudyTest extends BaseWritePermissionTestSetup {
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$store = $this->createDataMock(StudyStore::class, 'backupStudy');
		$this->createStoreMock(
			'getStudyStore',
			$store,
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$obj = new BackupStudy();
		
		$obj->exec();
		$this->assertDataMock('backupStudy', [$this->studyId]);
	}
}