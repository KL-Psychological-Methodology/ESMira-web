<?php

namespace backend\noJs\pages;

use backend\noJs\pages\Home;
use PHPUnit\Framework\MockObject\Stub;
use testConfigs\BaseNoJsTestSetup;

require_once __DIR__ . '/../../../autoload.php';

class HomeTest extends BaseNoJsTestSetup {
	
	function test() {
		$obj = new Home();
		
		$this->assertNotEmpty($obj->getContent());
		$this->assertEmpty($obj->getTitle());
	}
}