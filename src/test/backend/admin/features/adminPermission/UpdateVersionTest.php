<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\ToggleAdmin;
use backend\admin\features\adminPermission\UpdateVersion;
use backend\exceptions\PageFlowException;
use backend\Permission;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class UpdateVersionTest extends BaseAdminPermissionTestSetup {
	
	function test() {
		$obj = new UpdateVersion();
		$this->assertTrue($obj->testVersionCheck('1.2.3', '1.2.4'));
		$this->assertTrue($obj->testVersionCheck('1.2.3', '1.3.2'));
		$this->assertTrue($obj->testVersionCheck('1.2.3', '2.3.3'));
		
		$this->assertFalse($obj->testVersionCheck('1.2.3', '1.2.3'));
		$this->assertFalse($obj->testVersionCheck('1.2.3', '1.1.4'));
		$this->assertFalse($obj->testVersionCheck('2.2.3', '1.3.3'));
		
		$this->assertTrue($obj->testVersionCheck('1.2.3-alpha.4', '1.2.3'));
		$this->assertFalse($obj->testVersionCheck('1.2.3', '1.2.3-alpha.4'));
		
		$this->assertTrue($obj->testVersionCheck('1.2.3-alpha.4', '1.2.3-alpha.5'));
		$this->assertTrue($obj->testVersionCheck('1.2.3-alpha.4', '1.2.4-alpha.3'));
		$this->assertTrue($obj->testVersionCheck('1.2.3-alpha.4', '1.3.3-alpha.2'));
		$this->assertTrue($obj->testVersionCheck('1.2.3-alpha.4', '2.2.2-alpha.4'));
		
		$this->assertFalse($obj->testVersionCheck('1.2.3-alpha.4', '1.2.3-alpha.4'));
		$this->assertFalse($obj->testVersionCheck('1.2.3-alpha.4', '1.2.3-alpha.3'));
		$this->assertFalse($obj->testVersionCheck('1.2.3-alpha.4', '1.2.2-alpha.5'));
		$this->assertFalse($obj->testVersionCheck('1.2.3-alpha.4', '1.1.4-alpha.5'));
		$this->assertFalse($obj->testVersionCheck('2.2.3-alpha.4', '1.1.4-alpha.5'));
	}
}