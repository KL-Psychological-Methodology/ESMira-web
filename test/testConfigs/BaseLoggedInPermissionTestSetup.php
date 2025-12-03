<?php

namespace testConfigs;

use backend\Permission;
use backend\subStores\AccountStore;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../autoload.php';

abstract class BaseLoggedInPermissionTestSetup extends BaseNoPermissionTestSetup {
	private $accountName = 'loginUser';
	protected $studyId = 123;
	
	function setUp(): void {
		parent::setUp();
		Permission::setLoggedIn($this->accountName);
		$this->setPost(['study_id' => $this->studyId]);
	}
	
	protected function setUpAccountStoreObserver(): Stub {
		$accountStore = $this->createMock(AccountStore::class);
		
		$accountStore->expects($this->any())
			->method('checkAccountLogin')
			->willReturn(true);
		return $accountStore;
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		$this->createStoreMock('getAccountStore', $this->setUpAccountStoreObserver(), $observer);
		return $observer;
	}
}