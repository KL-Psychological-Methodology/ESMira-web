<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\SaveServerConfigs;
use backend\Configs;
use backend\Main;
use backend\PageFlowException;
use backend\Paths;
use backend\subStores\ServerStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class SaveServerConfigsTest extends BaseAdminPermissionTestSetup {
	private $serverNameContent1 = 'serverNameContent1';
	private $serverNameContent2 = 'serverNameContent2';
	private $serverNameContent3 = 'serverNameContent3';
	
	private $impressumContent1 = 'impressumContent1';
	private $impressumContent2 = 'impressumContent2';
	private $impressumContent3 = 'impressumContent3';
	
	private $privacyPolicyContent1 = 'privacyPolicyContent1';
	private $privacyPolicyContent2 = 'privacyPolicyContent2';
	private $privacyPolicyContent3 = 'privacyPolicyContent3';
	
	protected function tearDown(): void {
		parent::tearDown();
		if(file_exists(Paths::FILE_CONFIG))
			unlink(Paths::FILE_CONFIG);
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$store = $this->createDataMock(ServerStore::class, 'saveImpressum');
		$this->addDataMock($store, 'deleteImpressum');
		$this->addDataMock($store, 'savePrivacyPolicy');
		$this->addDataMock($store, 'deletePrivacyPolicy');
		
		$this->createStoreMock(
			'getServerStore',
			$store,
			$observer
		);
		return $observer;
	}
	
	function test_saveServerName() {
		Configs::injectConfig('configs.langCodes.injected.php');
		$defaultPostInput = [
			'_' => [
				'serverName' => $this->serverNameContent1,
				'impressum' => '',
				'privacyPolicy' => ''
			],
			'fr' => [
				'serverName' => $this->serverNameContent2,
				'impressum' => '',
				'privacyPolicy' => ''
			],
			'de' => [
				'serverName' => $this->serverNameContent3,
				'impressum' => '',
				'privacyPolicy' => ''
			]
		];
		Main::$defaultPostInput = json_encode($defaultPostInput);
		
		$obj = new SaveServerConfigs();
		$obj->exec();
		
		$this->assertEquals(['_' => $this->serverNameContent1, 'fr' => $this->serverNameContent2, 'de' => $this->serverNameContent3], Configs::get('serverName'));
		$this->assertEquals(['fr', 'de'], Configs::get('langCodes'));
	}
	
	function test_short_saveServerName() {
		$defaultPostInput = [
			'_' => [
				'serverName' => '12',
				'impressum' => '',
				'privacyPolicy' => ''
			]
		];
		Main::$defaultPostInput = json_encode($defaultPostInput);
		
		$this->expectException(PageFlowException::class);
		$obj = new SaveServerConfigs();
		$obj->exec();
	}
	function test_long_saveServerName() {
		$defaultPostInput = [
			'_' => [
				'serverName' => '1234567890123456789012345678901',
				'impressum' => '',
				'privacyPolicy' => ''
			]
		];
		Main::$defaultPostInput = json_encode($defaultPostInput);
		
		$this->expectException(PageFlowException::class);
		$obj = new SaveServerConfigs();
		$obj->exec();
	}
	function test_saveServerName_with_forbidden_characters() {
		$defaultPostInput = [
			'_' => [
				'serverName' => 'name$',
				'impressum' => '',
				'privacyPolicy' => ''
			]
		];
		Main::$defaultPostInput = json_encode($defaultPostInput);
		
		$this->expectException(PageFlowException::class);
		$obj = new SaveServerConfigs();
		$obj->exec();
	}
	
	function test_save_and_delete_impressum() {
		Configs::injectConfig('configs.langCodes.injected.php');
		$defaultPostInput = [
			'_' => [
				'serverName' => $this->serverNameContent1,
				'impressum' => $this->impressumContent1,
				'privacyPolicy' => ''
			],
			'fr' => [
				'serverName' => $this->serverNameContent1,
				'impressum' => '',
				'privacyPolicy' => ''
			],
			'de' => [
				'serverName' => $this->serverNameContent1,
				'impressum' => $this->impressumContent2,
				'privacyPolicy' => ''
			]
		];
		Main::$defaultPostInput = json_encode($defaultPostInput);
		
		$obj = new SaveServerConfigs();
		$obj->exec();
		
		$this->assertDataMock('saveImpressum', [$this->impressumContent1, '_'], [$this->impressumContent2, 'de']);
		$this->assertDataMock('deleteImpressum', ['fr'], ['en']);
	}
	
	function test_save_and_delete_privacyPolicy() {
		Configs::injectConfig('configs.langCodes.injected.php');
		$defaultPostInput = [
			'_' => [
				'serverName' => $this->serverNameContent1,
				'impressum' => '',
				'privacyPolicy' => $this->privacyPolicyContent1
			],
			'fr' => [
				'serverName' => $this->serverNameContent1,
				'impressum' => '',
				'privacyPolicy' => ''
			],
			'de' => [
				'serverName' => $this->serverNameContent1,
				'impressum' => '',
				'privacyPolicy' => $this->privacyPolicyContent2
			]
		];
		Main::$defaultPostInput = json_encode($defaultPostInput);
		
		$obj = new SaveServerConfigs();
		$obj->exec();
		
		$this->assertDataMock('savePrivacyPolicy', [$this->privacyPolicyContent1, '_'], [$this->privacyPolicyContent2, 'de']);
		$this->assertDataMock('deletePrivacyPolicy', ['fr'], ['en']);
	}
	
	function test_with_missing_data() {
		Main::$defaultPostInput = '';
		$obj = new SaveServerConfigs();
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
	function test_without_default_lang() {
		Main::$defaultPostInput = '{}';
		$obj = new SaveServerConfigs();
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
}