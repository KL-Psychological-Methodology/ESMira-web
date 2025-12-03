<?php

namespace backend\admin\features\noPermission;

use backend\admin\features\noPermission\Logout;
use backend\Permission;
use testConfigs\BaseLoggedInPermissionTestSetup;

require_once __DIR__ . '/../../../../autoload.php';

class LogoutTest extends BaseLoggedInPermissionTestSetup {
	function test() {
		$this->assertTrue(Permission::isLoggedIn());
		$obj = new Logout();
		$obj->exec();
		$this->assertFalse(Permission::isLoggedIn());
	}
}