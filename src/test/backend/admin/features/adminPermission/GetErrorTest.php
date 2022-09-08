<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\GetError;
use backend\subStores\ErrorReportStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class GetErrorTest extends BaseAdminPermissionTestSetup {
	private $reportContent = 'content';
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getErrorReportStore',
			$this->createDataMock(ErrorReportStore::class, 'getErrorReport', $this->reportContent),
			$observer
		);
		return $observer;
	}
	
	function test() {
		$obj = new GetError();
		
		$this->setGet([
			'timestamp' => 123
		]);
		$obj->execAndOutput();
		$this->expectOutputString($this->reportContent);
		$this->assertDataMock('getErrorReport', [123]);
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(GetError::class, [
			'timestamp' => 123,
		], true, true);
	}
}