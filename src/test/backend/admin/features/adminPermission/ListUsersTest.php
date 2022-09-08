<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\ListUsers;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class ListUsersTest extends BaseAdminPermissionTestSetup {
	private $userList = ['user1', 'user2'];
	private $permissions = ['admin' => true];
	protected function setUpUserStoreObserver(): Stub {
		$observer = parent::setUpUserStoreObserver();
		$observer->method('getUserList')
			->willReturn($this->userList);
		
		$this->addDataMock($observer, 'getPermissions', []);
		return $observer;
	}
	
	function test() {
		$obj = new ListUsers();
		
		$expected = [];
		foreach($this->userList as $username) {
			$permissions = $this->permissions;
			$permissions['username'] = $username;
			$expected[] = $permissions;
		}
		
		$this->assertEquals($expected, $obj->exec());
	}
}