<?php

namespace test\backend\admin\features\readPermission;

use backend\admin\features\readPermission\CreateMediaZip;
use backend\Configs;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\Paths;
use backend\subStores\ResponsesStore;
use backend\subStores\ServerStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseReadPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

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
	
	private function createMediaZipObj(array $expectedOutput): CreateMediaZip {
		$obj = $this->getMockBuilder(CreateMediaZip::class)
			->onlyMethods(['sendHeader', 'flushProgress'])
			->getMock();
		$obj->method('sendHeader');
		
		$count = 0;
		$obj->method('flushProgress')
			->willReturnCallback(function(string $content) use($expectedOutput, &$count) {
				$this->assertEquals($expectedOutput[$count++], $content);
			});
		return $obj;
	}
	
	function test() {
		$obj = $this->createMediaZipObj([
			"Start\n\n",
			"event: finished\ndata: \n\n"
		]);
		
		$obj->execAndOutput();
	}
}