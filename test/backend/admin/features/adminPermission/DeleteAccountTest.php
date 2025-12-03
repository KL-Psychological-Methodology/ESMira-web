<?php

namespace backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\DeleteAccount;
use PHPUnit\Framework\MockObject\Stub;
use testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../autoload.php';

class DeleteAccountTest extends BaseAdminPermissionTestSetup {
	private $accountName = 'user1';
	protected function setUpAccountStoreObserver(): Stub {
		$observer = parent::setUpAccountStoreObserver();
		
		$this->addDataMock($observer, 'removeAccount');
		return $observer;
	}
	
	function test() {
		$obj = new DeleteAccount();
		
		$this->assertDataMockFromPost($obj, 'removeAccount', [
			'accountName' => $this->accountName
		]);
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(DeleteAccount::class, [
			'accountName' => 'accountName',
		]);
	}
}