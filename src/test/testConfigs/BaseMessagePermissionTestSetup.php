<?php

namespace test\testConfigs;

use backend\admin\features\adminPermission\AddStudyPermission;
use backend\admin\features\noPermission\Login;
use backend\admin\NoPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\DataStoreInterface;
use backend\exceptions\PageFlowException;
use backend\Permission;
use backend\subStores\AccountStore;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use test\testConfigs\BaseLoggedInPermissionTestSetup;

require_once __DIR__ . '/../../backend/autoload.php';

abstract class BaseMessagePermissionTestSetup extends BaseLoggedInPermissionTestSetup {
	protected $studyId = 123;
	public function setUp(): void {
		parent::setUp();
		$this->setPost(['study_id' => $this->studyId]);
	}
	
	protected function setUpAccountStoreObserver(): Stub {
		$observer =  parent::setUpAccountStoreObserver();
		
		$observer
			->method('getPermissions')
			->willReturnCallback(function() {
				return ['msg' => [$this->studyId]];
			});
		
		return $observer;
	}
}