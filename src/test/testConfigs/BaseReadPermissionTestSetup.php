<?php

namespace test\testConfigs;

use backend\admin\features\adminPermission\AddAccountPermission;
use backend\admin\features\noPermission\Login;
use backend\admin\NoPermission;
use backend\Configs;
use backend\CriticalError;
use backend\DataStoreInterface;
use backend\PageFlowException;
use backend\Permission;
use backend\subStores\AccountStore;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use test\testConfigs\BaseLoggedInPermissionTestSetup;

require_once __DIR__ . '/../../backend/autoload.php';

abstract class BaseReadPermissionTestSetup extends BaseLoggedInPermissionTestSetup {
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
				return ['read' => [$this->studyId]];
			});
		
		return $observer;
	}
}