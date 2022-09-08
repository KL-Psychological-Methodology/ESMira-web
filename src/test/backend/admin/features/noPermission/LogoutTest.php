<?php

namespace test\backend\admin\features\noPermission;

use backend\admin\features\noPermission\Logout;
use backend\Permission;
use test\testConfigs\BaseLoggedInPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class LogoutTest extends BaseLoggedInPermissionTestSetup {
	function test() {
		$this->assertTrue(Permission::isLoggedIn());
		$obj = new Logout();
		$obj->exec();
		$this->assertFalse(Permission::isLoggedIn());
	}
}