<?php

namespace backend\noJs\pages;

use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseNoJsTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class HomeTest extends BaseNoJsTestSetup {
	
	function test() {
		$obj = new Home();
		
		$this->assertNotEmpty($obj->getContent());
		$this->assertEmpty($obj->getTitle());
	}
}