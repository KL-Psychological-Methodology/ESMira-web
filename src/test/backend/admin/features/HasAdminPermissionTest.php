<?php

namespace backend\admin\features;

use backend\admin\HasAdminPermission;
use backend\Permission;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../backend/autoload.php';

class HasAdminPermissionTest extends BaseAdminPermissionTestSetup {
	private function createObj(): HasAdminPermission {
		return new class extends HasAdminPermission {
			function exec(): array {
				return [];
			}
		};
	}
	
	public function test_with_admin_permission() {
		$this->isAdmin = true;
		$obj = $this->createObj();
		$this->assertEquals([], $obj->exec());
	}
	public function test_without_admin_permission() {
		$this->isAdmin = false;
		$this->expectErrorMessage('No permission');
		$this->createObj();
	}
	public function test_when_logged_out() {
		$this->setPost();
		Permission::setLoggedOut();
		$this->expectErrorMessage('No permission');
		$this->createObj();
	}
}