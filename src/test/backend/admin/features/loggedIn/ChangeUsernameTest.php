<?php

namespace test\backend\admin\features\loggedIn;

use backend\admin\features\loggedIn\ChangeUsername;
use backend\Main;
use backend\PageFlowException;
use backend\Permission;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class ChangeUsernameTest extends BaseAdminPermissionTestSetup {
	private $existingUser = 'existingUser';
	private $newUser = 'newUser';
	protected function setUpUserStoreObserver(): Stub {
		$observer = parent::setUpUserStoreObserver();
		
		$this->addDataMock($observer, 'changeUsername');
		$observer
			->method('doesUserExist')
			->willReturnCallback(function(string $username) {
				return $username == $this->existingUser;
			});
		return $observer;
	}
	
	function test() {
		$oldUsername = Permission::getUser();
		$obj = new ChangeUsername();
		
		$this->setPost([
			'new_user' => $this->newUser
		]);
		
		Main::setCookie('user', $oldUsername, time()+31536000);
		$obj->exec();
		$this->assertDataMock('changeUsername', array_values([$oldUsername, $this->newUser]));
		$this->assertEquals($this->newUser, Permission::getUser());
		$this->assertEquals($this->newUser, $_COOKIE['user']);
	}
	function test_with_exising_user_as_new_username() {$oldUsername = Permission::getUser();
		$obj = new ChangeUsername();
		
		$this->setPost([
			'new_user' => $this->existingUser
		]);
		
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
	function test_asAdmin() {
		$oldUsername = Permission::getUser();
		$this->setPost([
			'new_user' => $this->newUser,
			'user' => $this->existingUser
		]);
		
		//without admin, only own username should be used:
		$this->isAdmin = false;
		$obj = new ChangeUsername();
		$obj->exec();
		$this->assertDataMock('changeUsername', array_values([$oldUsername, $this->newUser]));
		
		//with admin:
		$this->isAdmin = true;
		$obj = new ChangeUsername();
		
		$obj->exec();
		$this->assertDataMock('changeUsername', array_values([$this->existingUser, $this->newUser]));
		
		$this->setPost([
			'new_user' => $this->newUser,
			'user' => 'notExisting'
		]);
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
	function test_with_short_username() {
		$obj = new ChangeUsername();
		
		$this->expectException(PageFlowException::class);
		$this->setPost([
			'new_user' => '12'
		]);
		$obj->exec();
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(ChangeUsername::class, [
			'new_user' => 'user',
		]);
	}
}