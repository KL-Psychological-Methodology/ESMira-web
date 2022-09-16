<?php

namespace test\backend\admin\features\noPermission;

use backend\admin\features\noPermission\Login;
use backend\Permission;
use backend\subStores\LoginTokenStore;
use backend\subStores\AccountStore;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseNoPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class LoginTest extends BaseNoPermissionTestSetup {
	private $accountName = 'user1';
	private $password = 'pass1';
	private $infoContent = ['entry1', 'entry2'];
	
	function setUp(): void {
		parent::setUp();
		$_SERVER['REMOTE_ADDR'] = 'REMOTE_ADDR'; // is undefined in test environment
		$_SERVER['HTTP_USER_AGENT'] = 'HTTP_USER_AGENT'; // is undefined in test environment
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$loginTokenStore = $this->createMock(LoginTokenStore::class);
		$loginTokenStore->expects($this->any())
			->method('saveLoginToken')
			->willReturnCallback(function($accountName) {
				if($accountName != $this->accountName)
					throw new ExpectationFailedException("Used wrong accountName (\"$accountName\") for saveLoginToken()");
			});
		$this->createStoreMock(
			'getLoginTokenStore',
			$this->createDataMock(LoginTokenStore::class, 'saveLoginToken'),
			$observer
		);
		
		
		$accountStore = $this->createMock(AccountStore::class);
		$accountStore->expects($this->any())
			->method('checkAccountLogin')
			->willReturnCallback(function($accountName, $password) {
				return $accountName == $this->accountName && $password == $this->password;
			});
		$this->createStoreMock(
			'getAccountStore',
			$accountStore,
			$observer
		);
		
		return $observer;
	}
	
	function test_correctLogin() {
		$this->setPost([
			'accountName' => $this->accountName,
			'pass' => $this->password,
		]);
		$obj = new Login();
		$output = $obj->exec();
		$this->assertTrue($output['isLoggedIn']);
	}
	function test_with_rememberMe() {
		$this->setPost([
			'accountName' => $this->accountName,
			'pass' => $this->password,
			'rememberMe' => true
		]);
		$obj = new Login();
		$output = $obj->exec();
		$this->assertTrue($output['isLoggedIn']);
		$this->assertDataMock('saveLoginToken', [$this->accountName, Permission::getHashedToken($_COOKIE['token']), $_COOKIE['tokenId']]);
	}
	
	function test_with_missing_data() {
		$this->isInit = false;
		$this->assertMissingDataForFeatureObj(Login::class, [
			'accountName' => 'accountName',
			'pass' => 'pass',
		]);
	}
}