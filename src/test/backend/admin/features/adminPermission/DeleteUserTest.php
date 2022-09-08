<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\DeleteUser;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class DeleteUserTest extends BaseAdminPermissionTestSetup {
	private $username = 'user1';
	protected function setUpUserStoreObserver(): Stub {
		$observer = parent::setUpUserStoreObserver();
		
		$this->addDataMock($observer, 'removeUser');
		return $observer;
	}
	
	function test() {
		$obj = new DeleteUser();
		
		$this->assertDataMockFromPost($obj, 'removeUser', [
			'user' => $this->username
		]);
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(DeleteUser::class, [
			'user' => 'user',
		]);
	}
}