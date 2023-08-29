<?php

namespace test\backend\admin\features\noPermission;

use backend\admin\features\noPermission\GetPermissions;
use backend\Permission;
use backend\subStores\ErrorReportStore;
use backend\subStores\MessagesStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class GetPermissionsTest extends BaseAdminPermissionTestSetup {
	private $newMessages = [123, 456, 789];
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$messageStore = $this->createStub(MessagesStore::class);
		$messageStore->method('getStudiesWithUnreadMessagesForPermission')
			->willReturn($this->newMessages);
		$this->createStoreMock('getMessagesStore', $messageStore, $observer);
		
		$errorStore = $this->createStub(ErrorReportStore::class);
		$errorStore->method('hasErrorReports')
			->willReturn(true);
		$this->createStoreMock('getErrorReportStore', $errorStore, $observer);
		
		return $observer;
	}
	
	function test_without_init() {
		$this->isInit = false;
		
		$obj = new GetPermissions();
		$this->assertEquals(['init_esmira' => true], $obj->exec());
	}
	function test_without_login() {
		Permission::setLoggedOut();
		
		$obj = new GetPermissions();
		$this->assertEquals(['isLoggedIn' => false], $obj->exec());
	}
	
	function test_without_admin() {
		$this->isAdmin = false;
		
		$obj = new GetPermissions();
		
		$output = $obj->exec();
		
		$this->assertEquals(Permission::getAccountName(), $output['accountName']);
		$this->assertTrue($output['isLoggedIn']);
		$this->assertEquals(['admin' => $this->isAdmin], $output['permissions']); //set in parent::setUpAccountStoreObserver()
		$this->assertArrayNotHasKey('isAdmin', $output);
		$this->assertEquals($this->newMessages, $output['newMessages']);
	}
	function test_with_admin() {
		$obj = new GetPermissions();
		
		$output = $obj->exec();
		
		$this->assertEquals(Permission::getAccountName(), $output['accountName']);
		$this->assertTrue($output['isLoggedIn']);
		$this->assertTrue($output['isAdmin']);
		$this->assertTrue($output['hasErrors']);
		$this->assertEquals($this->newMessages, $output['newMessages']);
	}
}