<?php

namespace test\backend\admin\features\loggedIn;

use backend\admin\features\loggedIn\ChangePassword;
use backend\PageFlowException;
use backend\Permission;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class ChangePasswordTest extends BaseAdminPermissionTestSetup {
	private $existingUser = 'existingUser';
	private $password = '123456789012';
	protected function setUpUserStoreObserver(): Stub {
		$observer = parent::setUpUserStoreObserver();
		
		$this->addDataMock($observer, 'setUser');
		$observer
			->method('doesUserExist')
			->willReturnCallback(function(string $username) {
				return $username == $this->existingUser;
			});
		return $observer;
	}
	
	function test() {
		$obj = new ChangePassword();
		
		$this->setPost([
			'new_pass' => $this->password
		]);
		$obj->exec();
		$this->assertDataMock('setUser', array_values([Permission::getUser(), $this->password]));
	}
	function test_asAdmin() {
		$this->setPost([
			'new_pass' => $this->password,
			'user' => $this->existingUser
		]);
		
		//without admin, only own username should be used:
		$this->isAdmin = false;
		$obj = new ChangePassword();
		$obj->exec();
		$this->assertDataMock('setUser', array_values([Permission::getUser(), $this->password]));
		
		//with admin:
		$this->isAdmin = true;
		$obj = new ChangePassword();
		
		$obj->exec();
		$this->assertDataMock('setUser', array_values([$this->existingUser, $this->password]));
		
		$this->setPost([
			'new_pass' => $this->password,
			'user' => 'notExisting'
		]);
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
	function test_with_short_password() {
		$obj = new ChangePassword();
		
		$this->expectException(PageFlowException::class);
		$this->setPost([
			'new_pass' => '12345678901'
		]);
		$obj->exec();
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(ChangePassword::class, [
			'new_pass' => 'pass',
		]);
	}
}