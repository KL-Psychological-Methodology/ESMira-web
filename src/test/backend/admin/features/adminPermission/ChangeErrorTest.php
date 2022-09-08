<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\ChangeError;
use backend\dataClasses\ErrorReportInfo;
use backend\subStores\ErrorReportStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';


class ChangeErrorTest extends BaseAdminPermissionTestSetup {
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getErrorReportStore',
			$this->createDataMock(ErrorReportStore::class, 'changeErrorReport'),
			$observer
		);
		return $observer;
	}
	
	function test() {
		$obj = new ChangeError();
		
		$this->assertDataMockFromPost($obj, 'changeErrorReport', [
			'timestamp' => 123,
			'note' => 'note',
			'seen' => false
		], [new ErrorReportInfo(123, 'note', false)]);
		
		$this->assertDataMockFromPost($obj, 'changeErrorReport', [
			'timestamp' => 1234,
			'note' => 'note2',
			'seen' => true
		], [new ErrorReportInfo(1234, 'note2', true)]);
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(ChangeError::class, [
			'timestamp' => 123,
			'seen' => false,
			'note' => 'note'
		]);
	}
}