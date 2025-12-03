<?php

namespace api;

use backend\JsonOutput;
use testConfigs\BaseApiTestSetup;

require_once __DIR__ . '/../autoload.php';

class AdminTest extends BaseApiTestSetup {
	
	function test() {
		$this->setGet([
			'type' => 'GetPermissions',
		]);
		$this->expectOutputString(JsonOutput::successObj(['isLoggedIn' => false]));
		require DIR_BASE .'/api/admin.php';
	}
	
	function test_with_faulty_type() {
		$this->setGet([
			'type' => 'something_wrong',
		]);
		
		$this->expectOutputString(JsonOutput::error('Unexpected request'));
		require DIR_BASE .'/api/admin.php';
	}
	
	function test_with_missing_data() {
		$this->isInit = true;
		$this->assertMissingDataForApi(
			[
				'type' => 'type'
			],
			'admin'
		);
	}
}