<?php

namespace backend\noJs\pages;

use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseNoJsTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class AppInstallTest extends BaseNoJsTestSetup {
	
	protected $configs = [
		123 => [
			'id' => 123,
			'title' => 'title1',
			'webInstallInstructions' => 'webInstallInstructions1',
			'studyDescription' => 'studyDescription1',
			'contactEmail' => 'contactEmail1',
			'publishedAndroid' => true,
			'publishedIOS' => false,
			'informedConsentForm' => 'informedConsentForm1'
		],
		234 => [
			'id' => 234,
			'title' => 'title1',
			'webInstallInstructions' => 'webInstallInstructions1',
			'studyDescription' => 'studyDescription1',
			'contactEmail' => 'contactEmail1',
			'publishedAndroid' => false,
			'publishedIOS' => true,
			'informedConsentForm' => 'informedConsentForm1'
		],
	];
	
	function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		$this->setUpForStudyData($observer);
		return $observer;
	}
	
	function test_without_accessKey() {
		$_SERVER['HTTP_HOST'] = 'host.name';
		$_SERVER['SCRIPT_NAME'] = '/sub/folder/index_nojs.php';
		$this->setGet(['id' => 123]);
		$obj = new AppInstall();
		$study = $this->configs[123];
		
		$this->assertEquals($study['title'], $obj->getTitle());
		$content = $obj->getContent();
		$this->assertStringContainsString($study['webInstallInstructions'], $content);
		$this->assertStringContainsString($study['studyDescription'], $content);
		$this->assertStringContainsString($study['contactEmail'], $content);
		$this->assertStringContainsString('play.google.com', $content);
		$this->assertStringNotContainsString('apps.apple.com', $content);
		$this->assertStringContainsString('esmira://host.name/sub/folder/123', $content);
		$this->assertStringContainsString($study['informedConsentForm'], $content);
	}
	
	function test_with_accessKey() {
		$_SERVER['HTTP_HOST'] = 'host.name';
		$_SERVER['SCRIPT_NAME'] = '/sub/folder/index_nojs.php';
		$this->setGet(['id' => 234, 'key' => 'key1']);
		$obj = new AppInstall();
		$study = $this->configs[234];
		
		$this->assertEquals($study['title'], $obj->getTitle());
		$content = $obj->getContent();
		$this->assertStringContainsString($study['webInstallInstructions'], $content);
		$this->assertStringContainsString($study['studyDescription'], $content);
		$this->assertStringContainsString($study['contactEmail'], $content);
		$this->assertStringNotContainsString('play.google.com', $content);
		$this->assertStringContainsString('apps.apple.com', $content);
		$this->assertStringContainsString('esmira://host.name/sub/folder/234-key1', $content);
		$this->assertStringContainsString($study['informedConsentForm'], $content);
	}
}