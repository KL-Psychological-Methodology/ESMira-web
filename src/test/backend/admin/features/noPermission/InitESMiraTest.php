<?php

namespace test\backend\admin\features\noPermission;

use backend\admin\features\noPermission\InitESMira;
use backend\Configs;
use backend\ESMiraInitializer;
use backend\PageFlowException;
use backend\Paths;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseNoPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class InitESMiraTest extends BaseNoPermissionTestSetup {
	private $configAdditionsKey = 'Unittest';
	private $configAdditionsValue = 123;
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$store = $this->createDataMock(ESMiraInitializer::class, 'create');
		$store->method('getConfigAdditions')
			->willReturn([
				$this->configAdditionsKey => $this->configAdditionsValue,
				'dataFolder_path' => 'some/path' //FileSystemBasics::writeServerConfigs() resets Configs which will lead to errors in GetPermission
			]);
		$this->createStoreMock(
			'getESMiraInitializer',
			$store,
			$observer
		);
		
		return $observer;
	}
	protected function tearDown(): void {
		parent::tearDown();
		if(file_exists(Paths::FILE_CONFIG))
			unlink(Paths::FILE_CONFIG);
	}
	
	function test() {
		$this->isInit = false;
		$user = 'user1';
		$pass = 'pass1';
		
		$this->setPost([
			'new_account' => $user,
			'pass' => $pass
		]);
		$obj = new InitESMira();
		$obj->exec();
		$this->assertDataMock('create', [$user, $pass]);
		$this->assertEquals($this->configAdditionsValue, Configs::get($this->configAdditionsKey));
	}
	
	function test_afterInit() {
		$this->isInit = true;
		$obj = new InitESMira();
		$this->expectException(PageFlowException::class);
		$this->expectErrorMessage('Disabled');
		$obj->exec();
	}
	
	function test_with_missing_data() {
		$this->isInit = false;
		$this->assertMissingDataForFeatureObj(InitESMira::class, [
			'new_account' => 'new_account',
			'pass' => 'pass',
		]);
	}
}