<?php

namespace backend\noJs\pages;

use backend\subStores\ServerStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseNoJsTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class LegalTest extends BaseNoJsTestSetup {
	private $impressum = [
		'en' => 'impressumEn',
		'de' => 'impressum_'
	];
	private $privacyPolicy = [
		'en' => 'privacyPolicyEn',
		'de' => 'privacyPolicyDe',
		'fr' => 'privacyPolicyFr'
	];
	function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		$serverStore = $this->createStub(ServerStore::class);
		$serverStore->method('getImpressum')
			->willReturnCallback(function(string $langCode): string {
				return $this->impressum[$langCode] ?? '';
			});
		$serverStore->method('getPrivacyPolicy')
			->willReturnCallback(function(string $langCode): string {
				return $this->privacyPolicy[$langCode] ?? '';
			});
		$this->createStoreMock('getServerStore', $serverStore, $observer);
		return $observer;
	}
	
	function test() {
		$obj = new Legal();
		
		$this->setGet(['lang' => 'en']);
		$content = $obj->getContent();
		$this->assertStringContainsString('impressumEn', $content);
		$this->assertStringContainsString('privacyPolicyEn', $content);
		$this->assertNotEmpty($obj->getTitle());
		
		$this->setGet(['lang' => 'de']);
		$content = $obj->getContent();
		$this->assertStringContainsString('impressum_', $content);
		$this->assertStringContainsString('privacyPolicyDe', $content);
	}
}