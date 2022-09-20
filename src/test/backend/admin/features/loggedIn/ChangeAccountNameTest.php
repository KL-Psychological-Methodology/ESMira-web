<?php

namespace test\backend\admin\features\loggedIn;

use backend\admin\features\loggedIn\ChangeAccountName;
use backend\Main;
use backend\exceptions\PageFlowException;
use backend\Permission;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class ChangeAccountNameTest extends BaseAdminPermissionTestSetup {
	private $existingAccountName = 'existingUser';
	private $newAccount = 'newUser';
	protected function setUpAccountStoreObserver(): Stub {
		$observer = parent::setUpAccountStoreObserver();
		
		$this->addDataMock($observer, 'changeAccountName');
		$observer
			->method('doesAccountExist')
			->willReturnCallback(function(string $accountName) {
				return $accountName == $this->existingAccountName;
			});
		return $observer;
	}
	
	function test() {
		$oldAccountName = Permission::getAccountName();
		$obj = new ChangeAccountName();
		
		$this->setPost([
			'new_account' => $this->newAccount
		]);
		
		Main::setCookie('account', $oldAccountName, time()+31536000);
		$obj->exec();
		$this->assertDataMock('changeAccountName', array_values([$oldAccountName, $this->newAccount]));
		$this->assertEquals($this->newAccount, Permission::getAccountName());
		$this->assertEquals($this->newAccount, $_COOKIE['account']);
	}
	function test_with_exising_account_as_new_accountName() {
		$obj = new ChangeAccountName();
		
		$this->setPost([
			'new_account' => $this->existingAccountName
		]);
		
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
	function test_asAdmin() {
		$oldAccountName = Permission::getAccountName();
		$this->setPost([
			'new_account' => $this->newAccount,
			'accountName' => $this->existingAccountName
		]);
		
		//without admin, only own accountName should be used:
		$this->isAdmin = false;
		$obj = new ChangeAccountName();
		$obj->exec();
		$this->assertDataMock('changeAccountName', array_values([$oldAccountName, $this->newAccount]));
		
		//with admin:
		$this->isAdmin = true;
		$obj = new ChangeAccountName();
		
		$obj->exec();
		$this->assertDataMock('changeAccountName', array_values([$this->existingAccountName, $this->newAccount]));
		
		$this->setPost([
			'new_account' => $this->newAccount,
			'accountName' => 'notExisting'
		]);
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
	function test_with_short_accountName() {
		$obj = new ChangeAccountName();
		
		$this->expectException(PageFlowException::class);
		$this->setPost([
			'new_account' => '12'
		]);
		$obj->exec();
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(ChangeAccountName::class, [
			'new_account' => 'accountName',
		]);
	}
}