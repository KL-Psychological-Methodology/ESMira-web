<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\GetServerConfig;
use backend\Configs;
use backend\subStores\ServerStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class GetServerConfigTest extends BaseAdminPermissionTestSetup {
	private $impressumContent = 'content';
	private $privacyPolicyContent = 'content';
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$store = $this->createDataMock(ServerStore::class, 'getImpressum', $this->impressumContent);
		$this->addDataMock($store, 'getPrivacyPolicy', $this->privacyPolicyContent);
		
		$this->createStoreMock(
			'getServerStore',
			$store,
			$observer
		);
		return $observer;
	}
	
	function test() {
		Configs::injectConfig('configs.langCodes.injected.php');
		$obj = new GetServerConfig();
		
		$serverSettings = Configs::getAll();
		$serverSettings['impressum'] = ['_' => $this->impressumContent, 'en' => $this->impressumContent, 'fr' => $this->impressumContent, 'de' => $this->impressumContent];
		$serverSettings['privacyPolicy'] = ['_' => $this->privacyPolicyContent, 'en' => $this->privacyPolicyContent, 'fr' => $this->privacyPolicyContent, 'de' => $this->privacyPolicyContent];
		$this->assertEquals($serverSettings, $obj->exec());
		
		$this->assertDataMock('getImpressum', ['en'], ['fr'], ['de'], ['_']);
		$this->assertDataMock('getPrivacyPolicy', ['en'], ['fr'], ['de'], ['_']);
	}
}