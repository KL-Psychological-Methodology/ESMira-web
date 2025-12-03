<?php

namespace backend\noJs\pages;

use backend\noJs\pages\ChangeLang;
use PHPUnit\Framework\MockObject\Stub;
use testConfigs\BaseNoJsTestSetup;

require_once __DIR__ . '/../../../autoload.php';

class ChangeLangTest extends BaseNoJsTestSetup {
	
	function test() {
		$obj = new ChangeLang();
		
		$content = $obj->getContent();
		$this->assertStringContainsString('lang=de', $content);
		$this->assertStringContainsString('lang=en', $content);
		$this->assertNotEmpty($obj->getTitle());
	}
}