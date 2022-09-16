<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\AddAccountPermission;
use test\testConfigs\BaseAdminPermissionTestSetup;
use backend\PageFlowException;
use PHPUnit\Framework\MockObject\Stub;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class AddAccountPermissionTest extends BaseAdminPermissionTestSetup {
	private $accountName = 'user1';
	protected function setUpAccountStoreObserver(): Stub {
		$observer = parent::setUpAccountStoreObserver();
		
		$this->addDataMock($observer, 'addStudyPermission');
		return $observer;
	}
	
	function test() {
		$obj = new AddAccountPermission();
		
		$this->assertDataMockFromPost($obj, 'addStudyPermission', [
			'accountName' => $this->accountName,
			'study_id' => $this->studyId,
			'permission' => 'read'
		]);
		
		$this->assertDataMockFromPost($obj, 'addStudyPermission', [
			'accountName' => $this->accountName,
			'study_id' => $this->studyId,
			'permission' => 'write'
		]);
		
		$this->assertDataMockFromPost($obj, 'addStudyPermission', [
			'accountName' => $this->accountName,
			'study_id' => $this->studyId,
			'permission' => 'msg'
		]);
		
		$_POST['permission'] = 'publish';
		$obj->exec();
		$this->assertDataMock('addStudyPermission',
			[$this->accountName, $this->studyId, 'publish'],
			[$this->accountName, $this->studyId, 'write']
		);
		
		$_POST['permission'] = 'faulty';
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
	
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(AddAccountPermission::class, [
			'accountName' => 'accountName',
			'permission' => 'write',
			'studyId' => 123
		]);
	}
}