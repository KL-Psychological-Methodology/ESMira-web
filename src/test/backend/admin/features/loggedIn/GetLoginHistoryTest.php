<?php

namespace test\backend\admin\features\loggedIn;

use backend\admin\features\loggedIn\GetLoginHistory;
use backend\Permission;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseLoggedInPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class GetLoginHistoryTest extends BaseLoggedInPermissionTestSetup {
	private $loginHistoryContent = 'content';
	protected function setUpAccountStoreObserver(): Stub {
		$observer = parent::setUpAccountStoreObserver();
		$this->addDataMock($observer, 'getLoginHistoryCsv', $this->loginHistoryContent);
		return $observer;
	}
	
	function test() {
		$obj = new GetLoginHistory();
		$obj->execAndOutput();
		$this->expectOutputString($this->loginHistoryContent);
		$this->assertDataMock('getLoginHistoryCsv', [Permission::getAccountName()]);
	}
}