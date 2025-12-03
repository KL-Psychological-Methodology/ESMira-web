<?php

namespace backend\noJs\pages;

use backend\noJs\pages\InformedConsent;
use PHPUnit\Framework\MockObject\Stub;
use testConfigs\BaseNoJsTestSetup;

require_once __DIR__ . '/../../../autoload.php';

class InformedConsentTest extends BaseNoJsTestSetup {
	
	protected $configs = [
		123 => ['id' => 123, 'informedConsentForm' => 'informedConsentForm1'],
	];
	
	function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		$this->setUpForStudyData($observer);
		return $observer;
	}
	
	function test() {
		$obj = new InformedConsent();
		
		$content = $obj->getContent();
		$this->assertStringContainsString('informedConsentForm1', $content);
		$this->assertNotEmpty($obj->getTitle());
	}
}