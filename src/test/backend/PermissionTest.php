<?php

namespace test\backend;

use backend\Configs;
use backend\DataStoreInterface;
use backend\PageFlowException;
use backend\Permission;
use backend\subStores\LoginTokenStore;
use backend\subStores\AccountStore;
use PHPUnit\Framework\MockObject\MockObject;
use test\testConfigs\BaseTestSetup;

require_once __DIR__ .'/../../backend/autoload.php';

class PermissionTest extends BaseTestSetup {
	/**
	 * @var DataStoreInterface
	 */
	private $dataStoreObserver;
	
	function setUp(): void {
		parent::setUp();
		$_SERVER['REMOTE_ADDR'] = 'REMOTE_ADDR'; // is undefined in test environment
		$_SERVER['HTTP_USER_AGENT'] = 'HTTP_USER_AGENT'; // is undefined in test environment
		
		$this->dataStoreObserver = $this->createMock(DataStoreInterface::class);
		
	}
	protected function tearDown(): void {
		parent::tearDown();
		Configs::resetAll();
		$_SESSION = [];
	}
	
	private function setUpAccountStore(callable $callback) {
		$accountStore = $this->createMock(AccountStore::class);
		$loginTokenStore = $this->createMock(LoginTokenStore::class);
		$callback($accountStore, $loginTokenStore);
		
		
		$this->dataStoreObserver->expects($this->any())
			->method('getAccountStore')
			->willReturnCallback(function() use ($accountStore): AccountStore {
				return $accountStore;
			});
		
		$this->dataStoreObserver->expects($this->any())
			->method('getLoginTokenStore')
			->willReturnCallback(function() use ($loginTokenStore): LoginTokenStore {
				return $loginTokenStore;
			});
		Configs::injectDataStore($this->dataStoreObserver);
	}
	
	function test_login_with_blocked_time() {
		$accountName = 'test';
		
		$_SERVER['REMOTE_ADDR'] = ''; // is undefined in test environment
		$_SERVER['HTTP_USER_AGENT'] = ''; // is undefined in test environment
		
		$this->setUpAccountStore(function(MockObject $accountStore) use ($accountName) {
			$accountStore->expects($this->once())
				->method('getAccountBlockedTime')
				->with(
					$this->equalTo($accountName)
				)
				->willReturn(10);
		});
		
		$this->expectException(PageFlowException::class);
		
		Permission::login($accountName, 'pass');
		$this->assertFalse(Permission::isLoggedIn());
	}
	
	function test_login_with_wrong_password() {
		$accountName = 'test';
		
		$_SERVER['REMOTE_ADDR'] = ''; // is undefined in test environment
		$_SERVER['HTTP_USER_AGENT'] = ''; // is undefined in test environment
		
		$this->setUpAccountStore(function(MockObject $accountStore) use ($accountName) {
			$accountStore->expects($this->once())
				->method('checkAccountLogin')
				->willReturn(false);
			
			$accountStore->expects($this->once())
				->method('createBlocking');
			
			$accountStore->expects($this->once())
				->method('addToLoginHistoryEntry')
				->with(
					$this->equalTo($accountName),
					$this->anything()
				);
		});
		
		$this->expectException(PageFlowException::class);
		
		Permission::login($accountName, 'pass');
		$this->assertFalse(Permission::isLoggedIn());
	}
	
	function test_login_with_correct_password() {
		$accountName = 'test';
		
		$this->setUpAccountStore(function(MockObject $accountStore) use ($accountName) {
			$accountStore->expects($this->once())
				->method('checkAccountLogin')
				->willReturn(true);
			
			$accountStore->expects($this->once())
				->method('removeBlocking')
				->with(
					$this->equalTo($accountName)
				);
			
			$accountStore->expects($this->once())
				->method('addToLoginHistoryEntry')
				->with(
					$this->equalTo($accountName),
					$this->anything()
				);
		});
		
		Permission::login($accountName, 'pass');
		$this->assertTrue(Permission::isLoggedIn());
		$this->assertEquals($accountName, Permission::getAccountName());
	}
	
	function test_setLogginIn_and_setLoggedOut() {
		$this->assertFalse(Permission::isLoggedIn());
		
		Permission::setLoggedIn('test');
		$this->assertTrue(Permission::isLoggedIn());
		
		Permission::setLoggedOut();
		$this->assertFalse(Permission::isLoggedIn());
	}
	
	function test_isLoggedIn_with_tokenCookie() {
		$accountName = 'accountName';
		$tokenId = 'tokenId';
		$token = 'token';
		
		$this->setUpAccountStore(function(MockObject $accountStore, MockObject $loginTokenStore) use ($accountName, $tokenId, $token) {
			$loginTokenStore->expects($this->once())
				->method('loginTokenExists')
				->with(
					$this->equalTo($accountName),
					$this->equalTo($tokenId)
				)
				->willReturn(true);
			
			$loginTokenStore->expects($this->once())
				->method('getLoginToken')
				->with(
					$this->equalTo($accountName),
					$this->equalTo($tokenId)
				)
				->willReturn(Permission::getHashedToken($token));
		});
		
		$this->assertFalse(Permission::isLoggedIn());
		
		$_COOKIE['account'] = $accountName;
		$_COOKIE['tokenId'] = $tokenId;
		$_COOKIE['token'] = $token;
		$this->assertTrue(Permission::isLoggedIn());
		$this->assertEquals($tokenId, Permission::getCurrentLoginTokenId());
	}
	
	function test_isLoggedIn_with_not_existing_tokenCookie() {
		$accountName = 'accountName';
		$tokenId = 'tokenId';
		$token = 'token';
		
		$this->setUpAccountStore(function(MockObject $accountStore, MockObject $loginTokenStore) use ($accountName, $tokenId, $token) {
			$loginTokenStore->expects($this->once())
				->method('loginTokenExists')
				->with(
					$this->equalTo($accountName),
					$this->equalTo($tokenId)
				)
				->willReturn(false);
		});
		Configs::injectDataStore($this->dataStoreObserver);
		
		$_COOKIE['account'] = $accountName;
		$_COOKIE['tokenId'] = $tokenId;
		$_COOKIE['token'] = $token;
		$this->assertFalse(Permission::isLoggedIn());
	}
	
	function test_isLoggedIn_with_broken_tokenCookie() {
		$accountName = 'accountName';
		$tokenId = 'tokenId';
		$token = 'token';
		
		$this->setUpAccountStore(function(MockObject $accountStore, MockObject $loginTokenStore) use ($accountName, $tokenId, $token) {
			$loginTokenStore->expects($this->once())
				->method('clearAllLoginToken');
			
			$loginTokenStore->expects($this->once())
				->method('loginTokenExists')
				->with(
					$this->equalTo($accountName),
					$this->equalTo($tokenId)
				)
				->willReturn(true);
			
			$loginTokenStore->expects($this->once())
				->method('getLoginToken')
				->with(
					$this->equalTo($accountName),
					$this->equalTo($tokenId)
				)
				->willReturn('something else');
		});
		Configs::injectDataStore($this->dataStoreObserver);
		
		$this->assertFalse(Permission::isLoggedIn());
		
		$_COOKIE['account'] = $accountName;
		$_COOKIE['tokenId'] = $tokenId;
		$_COOKIE['token'] = $token;
		$this->assertFalse(Permission::isLoggedIn());
	}
	
	function test_isAdmin() {
		$accountName = 'accountName';
		$this->setUpAccountStore(function(MockObject $accountStore) use ($accountName) {
			$accountStore->expects($this->any())
				->method('getPermissions')
				->with(
					$this->anything()
				)
				->willReturnCallback(function($user) use($accountName) {
					return $user == $accountName
						? ['admin' => true]
						: [];
				});
		});
		
		$_SESSION['account'] = $accountName;
		$this->assertTrue(Permission::isAdmin());
		
		$_SESSION['account'] = 'other';
		$this->assertFalse(Permission::isAdmin());
	}
	function test_hasPermission() {
		$accountName = 'accountName';
		$studyId = 123;
		$permCode = 'write';
		$this->setUpAccountStore(function(MockObject $accountStore) use ($accountName, $studyId, $permCode) {
			$accountStore->expects($this->any())
				->method('getPermissions')
				->with(
					$this->equalTo($accountName)
				)
				->willReturn([
					$permCode => [456, 789, $studyId, 147, 258]
				]);
		});
		
		$_SESSION['account'] = $accountName;
		
		$this->assertTrue(Permission::hasPermission($studyId, $permCode));
		$this->assertFalse(Permission::hasPermission(369, $permCode));
		$this->assertFalse(Permission::hasPermission($studyId, 'read'));
		$this->assertFalse(Permission::hasPermission(369, 'read'));
	}
}