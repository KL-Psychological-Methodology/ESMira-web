<?php

namespace backend\admin\features;

use backend\admin\HasReadPermission;
use backend\Permission;
use PHPUnit\Framework\MockObject\Stub;
use testConfigs\BaseLoggedInPermissionTestSetup;

require_once __DIR__ . '/../../../autoload.php';

class HasReadPermissionTest extends BaseLoggedInPermissionTestSetup {
	protected $readPermissions = [];
	protected $isAdmin = true;
	
	protected function setUpAccountStoreObserver(): Stub {
		$observer =  parent::setUpAccountStoreObserver();
		
		$observer
			->method('getPermissions')
			->willReturnCallback(function() {
				return ['admin' => $this->isAdmin, 'read' => $this->readPermissions];
			});
		
		return $observer;
	}
	
	private function createObj(): HasReadPermission {
		return new class extends HasReadPermission {
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
	public function test_with_read_permission() {
		$this->isAdmin = false;
		$this->readPermissions = [$this->studyId];
		$obj = $this->createObj();
		$this->assertEquals([], $obj->exec());
	}
	public function test_without_permissions() {
		$this->isAdmin = false;
		$this->readPermissions = [];
		$this->expectExceptionMessage('No permission');
		$obj = $this->createObj();
		$this->assertEquals([], $obj->exec());
	}
	public function test_without_studyId() {
		$this->setPost();
		$this->isAdmin = false;
		$this->readPermissions = [];
		$this->expectExceptionMessage('Missing study id');
		$this->createObj();
	}
	public function test_when_logged_out() {
		$this->setPost();
		Permission::setLoggedOut();
		$this->expectExceptionMessage('No permission');
		$this->createObj();
	}
}