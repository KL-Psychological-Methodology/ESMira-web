<?php

namespace backend\noJs\pages;

use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseNoJsTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class StudiesListTest extends BaseNoJsTestSetup {
	
	protected $configs = [
		123 => ['id' => 123, 'title' => 'study1'],
		234 => ['id' => 234, 'title' => 'study2', 'publishedWeb' => false],
		345 => ['id' => 345, 'title' => 'study3'],
		456 => ['id' => 456, 'title' => 'study4'],
	];
	
	function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		$this->setUpForStudyData($observer);
		return $observer;
	}
	
	function test() {
		$obj = new StudiesList();
		
		$content = $obj->getContent();
		$this->assertStringContainsString('?id=123', $content);
		$this->assertStringContainsString('app_install&id=234', $content);
		$this->assertStringContainsString('?id=345', $content);
		$this->assertStringContainsString('?id=456', $content);
		
		$this->assertStringContainsString($this->configs[123]['title'], $content);
		$this->assertStringContainsString($this->configs[234]['title'], $content);
		$this->assertStringContainsString($this->configs[345]['title'], $content);
		$this->assertStringContainsString($this->configs[456]['title'], $content);
	}
	function test_with_accessKey() {
		$this->setGet(['key' => 'key1']);
		$obj = new StudiesList();
		
		$content = $obj->getContent();
		$this->assertStringContainsString('?key=key1&id=123', $content);
		$this->assertStringContainsString('app_install&key=key1&id=234', $content);
		$this->assertStringContainsString('?key=key1&id=345', $content);
		$this->assertStringContainsString('?key=key1&id=456', $content);
	}
}