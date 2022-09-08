<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\GetLastActivities;
use backend\subStores\ResponsesStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class GetLastActivitiesTest extends BaseAdminPermissionTestSetup {
	private $timestamps = [123 => 123456789, 234 => 987654321];
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$store = $this->createStub(ResponsesStore::class);
		$store->method('getLastResponseTimestampOfStudies')
			->willReturn($this->timestamps);
		$this->createStoreMock('getResponsesStore', $store, $observer);
		
		return $observer;
	}
	
	function test() {
		$obj = new GetLastActivities();
		$this->assertEquals($this->timestamps, $obj->exec());
	}
}