<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\ListErrors;
use backend\subStores\ErrorReportStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class ListErrorsTest extends BaseAdminPermissionTestSetup {
	private $errorList = ['entry1', 'entry2'];
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$store = $this->createStub(ErrorReportStore::class);
		$store->method('getList')
			->willReturn($this->errorList);
		$this->createStoreMock('getErrorReportStore', $store, $observer);
		return $observer;
	}
	
	function test() {
		$obj = new ListErrors();
		
		$this->assertEquals($this->errorList, $obj->exec());
	}
}