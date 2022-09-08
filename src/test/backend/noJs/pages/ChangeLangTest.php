<?php

namespace backend\noJs\pages;

use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseNoJsTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class ChangeLangTest extends BaseNoJsTestSetup {
	
	function test() {
		$obj = new ChangeLang();
		
		$content = $obj->getContent();
		$this->assertStringContainsString('lang=de', $content);
		$this->assertStringContainsString('lang=en', $content);
		$this->assertNotEmpty($obj->getTitle());
	}
}