<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\CreateUser;
use backend\PageFlowException;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class CreateUserTest extends BaseAdminPermissionTestSetup {
	private $existingUsername = 'existingUser';
	private $newUsername = 'newUser';
	
	protected function setUpUserStoreObserver(): Stub {
		$observer = parent::setUpUserStoreObserver();
		$observer->expects($this->any())
			->method('doesUserExist')
			->willReturnCallback(function(string $username) {
				return $username == $this->existingUsername;
			});
		
		$this->addDataMock($observer, 'setUser');
		return $observer;
	}
	
	function test_with_existing_user() {
		$obj = new CreateUser();
		$this->expectException(PageFlowException::class);
		$this->setPost([
			'new_user' => $this->existingUsername,
			'pass' => 'pass'
		]);
		$obj->exec();
	}
	function test_with_new_user() {
		$obj = new CreateUser();
		
		$r = $this->assertDataMockFromPost($obj, 'setUser', [
			'new_user' => $this->newUsername,
			'pass' => 'pass'
		]);
		
		$this->assertEquals(['username' => $this->newUsername], $r);
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(CreateUser::class, [
			'new_user' => $this->existingUsername,
			'pass' => 'pass'
		]);
	}
}