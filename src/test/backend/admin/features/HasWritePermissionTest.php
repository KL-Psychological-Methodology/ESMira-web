<?php

namespace backend\admin\features;

use backend\admin\HasWritePermission;
use backend\Permission;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseLoggedInPermissionTestSetup;

require_once __DIR__ . '/../../../../backend/autoload.php';

class HasWritePermissionTest extends BaseLoggedInPermissionTestSetup {
	protected $writePermissions = [];
	protected $isAdmin = true;
	
	protected function setUpUserStoreObserver(): Stub {
		$observer =  parent::setUpUserStoreObserver();
		
		$observer
			->method('getPermissions')
			->willReturnCallback(function() {
				return ['admin' => $this->isAdmin, 'write' => $this->writePermissions];
			});
		
		return $observer;
	}
	
	private function createObj(): HasWritePermission {
		return new class extends HasWritePermission {
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
	public function test_with_write_permission() {
		$this->isAdmin = false;
		$this->writePermissions = [$this->studyId];
		$obj = $this->createObj();
		$this->assertEquals([], $obj->exec());
	}
	public function test_without_permissions() {
		$this->isAdmin = false;
		$this->writePermissions = [];
		$this->expectErrorMessage('No permission');
		$obj = $this->createObj();
		$this->assertEquals([], $obj->exec());
	}
	public function test_without_studyId() {
		$this->setPost();
		$this->isAdmin = false;
		$this->writePermissions = [];
		$this->expectErrorMessage('Missing study id');
		$this->createObj();
	}
	public function test_when_logged_out() {
		$this->setPost();
		Permission::setLoggedOut();
		$this->expectErrorMessage('No permission');
		$this->createObj();
	}
}