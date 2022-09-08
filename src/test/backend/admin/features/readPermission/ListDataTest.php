<?php

namespace test\backend\admin\features\readPermission;

use backend\admin\features\readPermission\ListData;
use backend\subStores\ResponsesStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseReadPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class ListDataTest extends BaseReadPermissionTestSetup {
	private $list = ['entry1', 'entry2'];
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getResponsesStore',
			$this->createDataMock(ResponsesStore::class, 'getResponseFilesList', $this->list),
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$obj = new ListData();
		
		$this->assertEquals($this->list, $obj->exec());
		$this->assertDataMock('getResponseFilesList', [$this->studyId]);
	}
}