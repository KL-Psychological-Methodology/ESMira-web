<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\CreateAccount;
use backend\exceptions\PageFlowException;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class CreateAccountTest extends BaseAdminPermissionTestSetup {
	private $existingAccountName = 'existingUser';
	private $newAccountName = 'newUser';
	
	protected function setUpAccountStoreObserver(): Stub {
		$observer = parent::setUpAccountStoreObserver();
		$observer->expects($this->any())
			->method('doesAccountExist')
			->willReturnCallback(function(string $accountName) {
				return $accountName == $this->existingAccountName;
			});
		
		$this->addDataMock($observer, 'setAccount');
		return $observer;
	}
	
	function test_with_existing_user() {
		$obj = new CreateAccount();
		$this->expectException(PageFlowException::class);
		$this->setPost([
			'new_account' => $this->existingAccountName,
			'pass' => 'pass'
		]);
		$obj->exec();
	}
	function test_with_new_account() {
		$obj = new CreateAccount();
		
		$r = $this->assertDataMockFromPost($obj, 'setAccount', [
			'new_account' => $this->newAccountName,
			'pass' => 'pass'
		]);
		
		$this->assertEquals(['accountName' => $this->newAccountName], $r);
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(CreateAccount::class, [
			'new_account' => $this->existingAccountName,
			'pass' => 'pass'
		]);
	}
}