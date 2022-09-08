<?php

namespace test\backend;

use backend\JsonOutput;
use backend\Main;
use test\testConfigs\BaseTestSetup;

require_once __DIR__ .'/../../backend/autoload.php';

class JsonOutputTest extends BaseTestSetup {
	function test_error() {
		$this->assertEquals(
			'{"success":false,"serverVersion":'.Main::SERVER_VERSION.',"error":"testString"}',
			JsonOutput::error('testString')
		);
	}
	function test_successString() {
		$this->assertEquals(
			'{"success":true,"serverVersion":'.Main::SERVER_VERSION.',"dataset":["testString"]}',
			JsonOutput::successString('["testString"]')
		);
	}
	function test_successObj() {
		$this->assertEquals(
			'{"success":true,"serverVersion":'.Main::SERVER_VERSION.',"dataset":["testString"]}',
			JsonOutput::successObj(["testString"])
		);
	}
	
}