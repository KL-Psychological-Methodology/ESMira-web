<?php

namespace backend\admin\features;

use backend\admin\HasMessagePermission;
use backend\Permission;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseLoggedInPermissionTestSetup;

require_once __DIR__ . '/../../../../backend/autoload.php';

class HasMessagePermissionTest extends BaseLoggedInPermissionTestSetup {
	protected $msgPermissions = [];
	protected $isAdmin = true;
	
	protected function setUpAccountStoreObserver(): Stub {
		$observer =  parent::setUpAccountStoreObserver();
		
		$observer
			->method('getPermissions')
			->willReturnCallback(function() {
				return ['admin' => $this->isAdmin, 'msg' => $this->msgPermissions];
			});
		
		return $observer;
	}
	
	private function createObj(): HasMessagePermission {
		return new class extends HasMessagePermission {
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
	public function test_has_msg_permission() {
		$this->isAdmin = false;
		$this->msgPermissions = [$this->studyId];
		$obj = $this->createObj();
		$this->assertEquals([], $obj->exec());
	}
	public function test_without_permissions() {
		$this->isAdmin = false;
		$this->msgPermissions = [];
		$this->expectErrorMessage('No permission');
		$obj = $this->createObj();
		$this->assertEquals([], $obj->exec());
	}
	public function test_without_studyId() {
		$this->setPost();
		$this->isAdmin = false;
		$this->msgPermissions = [];
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