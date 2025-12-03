<?php

namespace backend\admin\features\noPermission;

use backend\admin\features\noPermission\InitESMiraPrep;
use backend\ESMiraInitializer;
use backend\exceptions\PageFlowException;
use PHPUnit\Framework\MockObject\Stub;
use testConfigs\BaseNoPermissionTestSetup;

require_once __DIR__ . '/../../../../autoload.php';

class InitESMiraPrepTest extends BaseNoPermissionTestSetup {
	private $infoArray = ['key1' => 'value1'];
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$store = $this->createMock(ESMiraInitializer::class);
		$store->method('getInfoArray')
			->willReturn($this->infoArray);
		$this->createStoreMock(
			'getESMiraInitializer',
			$store,
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$this->isInit = false;
		$obj = new InitESMiraPrep();
		$obj->exec();
		$this->assertEquals($this->infoArray, $obj->exec());
	}
	
	function test_afterInit() {
		$this->isInit = true;
		$obj = new InitESMiraPrep();
		$this->expectException(PageFlowException::class);
		$this->expectErrorMessage('Disabled');
		$obj->exec();
	}
}