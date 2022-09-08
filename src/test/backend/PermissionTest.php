<?php

namespace test\backend;

use backend\Configs;
use backend\DataStoreInterface;
use backend\PageFlowException;
use backend\Permission;
use backend\subStores\LoginTokenStore;
use backend\subStores\UserStore;
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
	
	private function setUpUserStore(callable $callback) {
		$userStore = $this->createMock(UserStore::class);
		$loginTokenStore = $this->createMock(LoginTokenStore::class);
		$callback($userStore, $loginTokenStore);
		
		
		$this->dataStoreObserver->expects($this->any())
			->method('getUserStore')
			->willReturnCallback(function() use ($userStore): UserStore {
				return $userStore;
			});
		
		$this->dataStoreObserver->expects($this->any())
			->method('getLoginTokenStore')
			->willReturnCallback(function() use ($loginTokenStore): LoginTokenStore {
				return $loginTokenStore;
			});
		Configs::injectDataStore($this->dataStoreObserver);
	}
	
	function test_login_with_blocked_time() {
		$username = 'test';
		
		$_SERVER['REMOTE_ADDR'] = ''; // is undefined in test environment
		$_SERVER['HTTP_USER_AGENT'] = ''; // is undefined in test environment
		
		$this->setUpUserStore(function(MockObject $userStore) use ($username) {
			$userStore->expects($this->once())
				->method('getUserBlockedTime')
				->with(
					$this->equalTo($username)
				)
				->willReturn(10);
		});
		
		$this->expectException(PageFlowException::class);
		
		Permission::login($username, 'pass');
		$this->assertFalse(Permission::isLoggedIn());
	}
	
	function test_login_with_wrong_password() {
		$username = 'test';
		
		$_SERVER['REMOTE_ADDR'] = ''; // is undefined in test environment
		$_SERVER['HTTP_USER_AGENT'] = ''; // is undefined in test environment
		
		$this->setUpUserStore(function(MockObject $userStore) use ($username) {
			$userStore->expects($this->once())
				->method('checkUserLogin')
				->willReturn(false);
			
			$userStore->expects($this->once())
				->method('createBlocking');
			
			$userStore->expects($this->once())
				->method('addToLoginHistoryEntry')
				->with(
					$this->equalTo($username),
					$this->anything()
				);
		});
		
		$this->expectException(PageFlowException::class);
		
		Permission::login($username, 'pass');
		$this->assertFalse(Permission::isLoggedIn());
	}
	
	function test_login_with_correct_password() {
		$username = 'test';
		
		$this->setUpUserStore(function(MockObject $userStore) use ($username) {
			$userStore->expects($this->once())
				->method('checkUserLogin')
				->willReturn(true);
			
			$userStore->expects($this->once())
				->method('removeBlocking')
				->with(
					$this->equalTo($username)
				);
			
			$userStore->expects($this->once())
				->method('addToLoginHistoryEntry')
				->with(
					$this->equalTo($username),
					$this->anything()
				);
		});
		
		Permission::login($username, 'pass');
		$this->assertTrue(Permission::isLoggedIn());
		$this->assertEquals($username, Permission::getUser());
	}
	
	function test_setLogginIn_and_setLoggedOut() {
		$this->assertFalse(Permission::isLoggedIn());
		
		Permission::setLoggedIn('test');
		$this->assertTrue(Permission::isLoggedIn());
		
		Permission::setLoggedOut();
		$this->assertFalse(Permission::isLoggedIn());
	}
	
	function test_isLoggedIn_with_tokenCookie() {
		$username = 'username';
		$tokenId = 'tokenId';
		$token = 'token';
		
		$this->setUpUserStore(function(MockObject $userStore, MockObject $loginTokenStore) use ($username, $tokenId, $token) {
			$loginTokenStore->expects($this->once())
				->method('loginTokenExists')
				->with(
					$this->equalTo($username),
					$this->equalTo($tokenId)
				)
				->willReturn(true);
			
			$loginTokenStore->expects($this->once())
				->method('getLoginToken')
				->with(
					$this->equalTo($username),
					$this->equalTo($tokenId)
				)
				->willReturn(Permission::getHashedToken($token));
		});
		
		$this->assertFalse(Permission::isLoggedIn());
		
		$_COOKIE['user'] = $username;
		$_COOKIE['tokenId'] = $tokenId;
		$_COOKIE['token'] = $token;
		$this->assertTrue(Permission::isLoggedIn());
		$this->assertEquals($tokenId, Permission::getCurrentLoginTokenId());
	}
	
	function test_isLoggedIn_with_not_existing_tokenCookie() {
		$username = 'username';
		$tokenId = 'tokenId';
		$token = 'token';
		
		$this->setUpUserStore(function(MockObject $userStore, MockObject $loginTokenStore) use ($username, $tokenId, $token) {
			$loginTokenStore->expects($this->once())
				->method('loginTokenExists')
				->with(
					$this->equalTo($username),
					$this->equalTo($tokenId)
				)
				->willReturn(false);
		});
		Configs::injectDataStore($this->dataStoreObserver);
		
		$_COOKIE['user'] = $username;
		$_COOKIE['tokenId'] = $tokenId;
		$_COOKIE['token'] = $token;
		$this->assertFalse(Permission::isLoggedIn());
	}
	
	function test_isLoggedIn_with_broken_tokenCookie() {
		$username = 'username';
		$tokenId = 'tokenId';
		$token = 'token';
		
		$this->setUpUserStore(function(MockObject $userStore, MockObject $loginTokenStore) use ($username, $tokenId, $token) {
			$loginTokenStore->expects($this->once())
				->method('clearAllLoginToken');
			
			$loginTokenStore->expects($this->once())
				->method('loginTokenExists')
				->with(
					$this->equalTo($username),
					$this->equalTo($tokenId)
				)
				->willReturn(true);
			
			$loginTokenStore->expects($this->once())
				->method('getLoginToken')
				->with(
					$this->equalTo($username),
					$this->equalTo($tokenId)
				)
				->willReturn('something else');
		});
		Configs::injectDataStore($this->dataStoreObserver);
		
		$this->assertFalse(Permission::isLoggedIn());
		
		$_COOKIE['user'] = $username;
		$_COOKIE['tokenId'] = $tokenId;
		$_COOKIE['token'] = $token;
		$this->assertFalse(Permission::isLoggedIn());
	}
	
	function test_isAdmin() {
		$username = 'username';
		$this->setUpUserStore(function(MockObject $userStore) use ($username) {
			$userStore->expects($this->any())
				->method('getPermissions')
				->with(
					$this->anything()
				)
				->willReturnCallback(function($user) use($username) {
					return $user == $username
						? ['admin' => true]
						: [];
				});
		});
		
		$_SESSION['user'] = $username;
		$this->assertTrue(Permission::isAdmin());
		
		$_SESSION['user'] = 'other';
		$this->assertFalse(Permission::isAdmin());
	}
	function test_hasPermission() {
		$username = 'username';
		$studyId = 123;
		$permCode = 'write';
		$this->setUpUserStore(function(MockObject $userStore) use ($username, $studyId, $permCode) {
			$userStore->expects($this->any())
				->method('getPermissions')
				->with(
					$this->equalTo($username)
				)
				->willReturn([
					$permCode => [456, 789, $studyId, 147, 258]
				]);
		});
		
		$_SESSION['user'] = $username;
		
		$this->assertTrue(Permission::hasPermission($studyId, $permCode));
		$this->assertFalse(Permission::hasPermission(369, $permCode));
		$this->assertFalse(Permission::hasPermission($studyId, 'read'));
		$this->assertFalse(Permission::hasPermission(369, 'read'));
	}
}