<?php

namespace test\backend\admin\features\loggedIn;

use backend\admin\features\loggedIn\ChangePassword;
use backend\exceptions\PageFlowException;
use backend\Permission;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class ChangePasswordTest extends BaseAdminPermissionTestSetup {
	private $existingAccountName = 'existingUser';
	private $password = '123456789012';
	protected function setUpAccountStoreObserver(): Stub {
		$observer = parent::setUpAccountStoreObserver();
		
		$this->addDataMock($observer, 'setAccount');
		$observer
			->method('doesAccountExist')
			->willReturnCallback(function(string $accountName) {
				return $accountName == $this->existingAccountName;
			});
		return $observer;
	}
	
	function test() {
		$obj = new ChangePassword();
		
		$this->setPost([
			'new_pass' => $this->password
		]);
		$obj->exec();
		$this->assertDataMock('setAccount', array_values([Permission::getAccountName(), $this->password]));
	}
	function test_asAdmin() {
		$this->setPost([
			'new_pass' => $this->password,
			'accountName' => $this->existingAccountName
		]);
		
		//without admin, only own accountName should be used:
		$this->isAdmin = false;
		$obj = new ChangePassword();
		$obj->exec();
		$this->assertDataMock('setAccount', array_values([Permission::getAccountName(), $this->password]));
		
		//with admin:
		$this->isAdmin = true;
		$obj = new ChangePassword();
		
		$obj->exec();
		$this->assertDataMock('setAccount', array_values([$this->existingAccountName, $this->password]));
		
		$this->setPost([
			'new_pass' => $this->password,
			'accountName' => 'notExisting'
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