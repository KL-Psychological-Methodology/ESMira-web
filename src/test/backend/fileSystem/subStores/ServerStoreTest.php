<?php

namespace test\backend\fileSystem\subStores;

use backend\Configs;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class ServerStoreTest extends BaseDataFolderTestSetup {
	function test_saveImpressum_and_deleteImpressum_and_getImpressum() {
		$store = Configs::getDataStore()->getServerStore();
		
		$this->assertEquals('', $store->getImpressum('de'));
		
		$store->saveImpressum('impressum1', 'de');
		$store->saveImpressum('impressum2', 'en');
		$this->assertEquals('impressum1', $store->getImpressum('de'));
		$this->assertEquals('impressum2', $store->getImpressum('en'));
		$this->assertEquals('', $store->getImpressum('fr'));
		
		$store->deleteImpressum( 'en');
		$this->assertEquals('impressum1', $store->getImpressum('de'));
		$this->assertEquals('', $store->getImpressum('en'));
	}
	
	function test_savePrivacyPolicy_and_deletePrivacyPolicy_and_getPrivacyPolicy() {
		$store = Configs::getDataStore()->getServerStore();
		
		$this->assertEquals('', $store->getPrivacyPolicy('de'));
		
		$store->savePrivacyPolicy('content1', 'de');
		$store->savePrivacyPolicy('content2', 'en');
		$this->assertEquals('content1', $store->getPrivacyPolicy('de'));
		$this->assertEquals('content2', $store->getPrivacyPolicy('en'));
		$this->assertEquals('', $store->getPrivacyPolicy('fr'));
		
		$store->deletePrivacyPolicy( 'en');
		$this->assertEquals('content1', $store->getPrivacyPolicy('de'));
		$this->assertEquals('', $store->getPrivacyPolicy('en'));
	}
}