<?php

namespace backend\admin\features\readPermission;

use backend\Configs;
use backend\SSE;
use backend\subStores\ResponsesStore;
use PHPUnit\Framework\MockObject\Stub;
use testConfigs\BaseReadPermissionTestSetup;

require_once __DIR__ . '/../../../../autoload.php';

class CreateMediaZipTest extends BaseReadPermissionTestSetup {
	public function setUp(): void {
		parent::setUp();
		Configs::injectConfig('configs.dataFolder.injected.php');
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$store = $this->createMock(ResponsesStore::class);
		$store->method('createMediaZip');
		$this->createStoreMock('getResponsesStore', $store, $observer);
		
		return $observer;
	}
	
	function test() {
		$sse = $this->createMock(SSE::class);
		$sse->expects($this->once())
			->method('sendHeader');
		$sse->expects($this->once())
			->method('flushFinished');
		
		$obj = new CreateMediaZip($sse);
		
		$obj->execAndOutput();
	}
}