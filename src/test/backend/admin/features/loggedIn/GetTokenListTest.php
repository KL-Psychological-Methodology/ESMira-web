<?php

namespace test\backend\admin\features\loggedIn;

use backend\admin\features\loggedIn\GetTokenList;
use backend\Permission;
use backend\subStores\LoginTokenStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseLoggedInPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class GetTokenListTest extends BaseLoggedInPermissionTestSetup {
	private $loginTokenList = ['entry1', 'entry2'];
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getLoginTokenStore',
			$this->createDataMock(LoginTokenStore::class, 'getLoginTokenList', $this->loginTokenList),
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$obj = new GetTokenList();
		$obj->exec();
		$this->assertDataMock('getLoginTokenList', [Permission::getUser()]);
	}
}