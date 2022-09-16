<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\ListAccounts;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class ListAccountsTest extends BaseAdminPermissionTestSetup {
	private $accountList = ['user1', 'user2'];
	private $permissions = ['admin' => true];
	protected function setUpAccountStoreObserver(): Stub {
		$observer = parent::setUpAccountStoreObserver();
		$observer->method('getAccountList')
			->willReturn($this->accountList);
		
		$this->addDataMock($observer, 'getPermissions', []);
		return $observer;
	}
	
	function test() {
		$obj = new ListAccounts();
		
		$expected = [];
		foreach($this->accountList as $accountName) {
			$permissions = $this->permissions;
			$permissions['accountName'] = $accountName;
			$expected[] = $permissions;
		}
		
		$this->assertEquals($expected, $obj->exec());
	}
}