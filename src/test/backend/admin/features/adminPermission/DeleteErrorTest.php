<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\DeleteError;
use backend\subStores\ErrorReportStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class DeleteErrorTest extends BaseAdminPermissionTestSetup {
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getErrorReportStore',
			$this->createDataMock(ErrorReportStore::class, 'removeErrorReport'),
			$observer
		);
		return $observer;
	}
	
	
	function test() {
		$obj = new DeleteError();
		
		$this->assertDataMockFromPost($obj, 'removeErrorReport', [
			'timestamp' => 123
		]);
		
		$this->assertDataMockFromPost($obj, 'removeErrorReport', [
			'timestamp' => 1234
		]);
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(DeleteError::class, [
			'timestamp' => 123
		]);
	}
}