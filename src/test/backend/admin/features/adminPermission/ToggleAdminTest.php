<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\ToggleAccountPermission;
use backend\exceptions\PageFlowException;
use backend\Permission;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class ToggleAdminTest extends BaseAdminPermissionTestSetup {
	private $accountName = 'user1';
	protected function setUpAccountStoreObserver(): Stub {
		$observer = parent::setUpAccountStoreObserver();
		
		$this->addDataMock($observer, 'setAdminPermission');
		return $observer;
	}
	
	function test() {
		$obj = new ToggleAccountPermission();
		
		$this->assertDataMockFromPost($obj, 'setAdminPermission', [
			'accountName' => $this->accountName,
			'admin' => true
		]);
		
		
		$this->setPost([
			'accountName' => $this->accountName
		]);
		$obj->exec();
		$this->assertDataMock('setAdminPermission', [$this->accountName, false]);
	}
	
	function test_remove_own_admin_permission() {
		$obj = new ToggleAccountPermission();
		
		$this->expectException(PageFlowException::class);
		$this->assertDataMockFromPost($obj, 'setAdminPermission', [
			'accountName' => Permission::getAccountName()
		]);
	}
	
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(ToggleAccountPermission::class, [
			'accountName' => 'accountName',
		]);
	}
}