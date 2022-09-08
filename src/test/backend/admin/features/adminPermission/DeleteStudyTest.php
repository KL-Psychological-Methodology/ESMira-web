<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\DeleteStudy;
use backend\subStores\StudyStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class DeleteStudyTest extends BaseAdminPermissionTestSetup {
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getStudyStore',
			$this->createDataMock(StudyStore::class, 'delete'),
			$observer
		);
		return $observer;
	}
	
	
	function test() {
		$obj = new DeleteStudy();
		
		$this->assertDataMockFromPost($obj, 'delete', [
			'studyId' => $this->studyId
		]);
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(DeleteStudy::class, [
			'studyId' => $this->studyId
		]);
	}
}