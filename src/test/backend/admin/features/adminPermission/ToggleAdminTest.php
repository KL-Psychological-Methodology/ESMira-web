<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\ToggleAdmin;
use backend\PageFlowException;
use backend\Permission;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class ToggleAdminTest extends BaseAdminPermissionTestSetup {
	private $username = 'user1';
	protected function setUpUserStoreObserver(): Stub {
		$observer = parent::setUpUserStoreObserver();
		
		$this->addDataMock($observer, 'setAdminPermission');
		return $observer;
	}
	
	function test() {
		$obj = new ToggleAdmin();
		
		$this->assertDataMockFromPost($obj, 'setAdminPermission', [
			'user' => $this->username,
			'admin' => true
		]);
		
		
		$this->setPost([
			'user' => $this->username
		]);
		$obj->exec();
		$this->assertDataMock('setAdminPermission', [$this->username, false]);
	}
	
	function test_remove_own_admin_permission() {
		$obj = new ToggleAdmin();
		
		$this->expectException(PageFlowException::class);
		$this->assertDataMockFromPost($obj, 'setAdminPermission', [
			'user' => Permission::getUser()
		]);
	}
	
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(ToggleAdmin::class, [
			'user' => 'user',
		]);
	}
}