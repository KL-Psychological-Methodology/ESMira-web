<?php

namespace test\backend\admin\features\loggedIn;

use backend\admin\features\loggedIn\RemoveToken;
use backend\Permission;
use backend\subStores\LoginTokenStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseLoggedInPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class RemoveTokenTest extends BaseLoggedInPermissionTestSetup {
	private $loginTokenList = ['entry1', 'entry2'];
	private $tokenId = 'tokenId';
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getLoginTokenStore',
			$this->createDataMock(LoginTokenStore::class, 'removeLoginToken', $this->loginTokenList),
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$this->setPost(['token_id' => $this->tokenId]);
		$obj = new RemoveToken();
		$obj->exec();
		$this->assertDataMock('removeLoginToken', [Permission::getAccountName(), $this->tokenId]);
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(RemoveToken::class, [
			'token_id' => 'token',
		]);
	}
}