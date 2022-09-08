<?php

namespace test\api;

use backend\JsonOutput;
use backend\subStores\ServerStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseApiTestSetup;

require_once __DIR__ .'/../../backend/autoload.php';

class LegalTest extends BaseApiTestSetup {
	private $privacyPolicy = 'privacyPolicy';
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$serverStore = $this->createDataMock(ServerStore::class, 'getImpressum', '');
		$this->addDataMock($serverStore, 'getPrivacyPolicy', $this->privacyPolicy);
		$this->createStoreMock('getServerStore', $serverStore, $observer);
		
		return $observer;
	}
	
	function test() {
		$this->setGet(['lang' => 'en']);
		require DIR_BASE .'/api/legal.php';
		$this->assertDataMock('getImpressum', ['en'], ['_']);
		$this->assertDataMock('getPrivacyPolicy', ['en']);
		$this->expectOutputString(JsonOutput::successObj([
			'impressum' => '',
			'privacyPolicy' => $this->privacyPolicy
		]));
	}
	
	function test_without_init() {
		$this->assertIsInit('legal');
	}
}