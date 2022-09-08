<?php

namespace test\testConfigs;

use backend\admin\features\adminPermission\AddUserPermission;
use test\testConfigs\BaseNoPermissionTestSetup;
use backend\admin\features\noPermission\Login;
use backend\admin\NoPermission;
use backend\Configs;
use backend\CriticalError;
use backend\DataStoreInterface;
use backend\PageFlowException;
use backend\Permission;
use backend\subStores\UserStore;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../backend/autoload.php';

abstract class BaseLoggedInPermissionTestSetup extends BaseNoPermissionTestSetup {
	private $username = 'loginUser';
	protected $studyId = 123;
	
	function setUp(): void {
		parent::setUp();
		Permission::setLoggedIn($this->username);
		$this->setPost(['study_id' => $this->studyId]);
	}
	
	protected function setUpUserStoreObserver(): Stub {
		$userStore = $this->createMock(UserStore::class);
		
		$userStore->expects($this->any())
			->method('checkUserLogin')
			->willReturn(true);
		return $userStore;
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		$this->createStoreMock('getUserStore', $this->setUpUserStoreObserver(), $observer);
		return $observer;
	}
}