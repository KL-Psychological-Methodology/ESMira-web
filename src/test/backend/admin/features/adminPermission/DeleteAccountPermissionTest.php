<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\DeleteAccountPermission;
use backend\exceptions\PageFlowException;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class DeleteAccountPermissionTest extends BaseAdminPermissionTestSetup {
	private $accountName = 'user1';
	protected function setUpAccountStoreObserver(): Stub {
		$observer = parent::setUpAccountStoreObserver();
		
		$this->addDataMock($observer, 'removeStudyPermission');
		return $observer;
	}
	
	function test() {
		$obj = new DeleteAccountPermission();
		
		$this->assertDataMockFromPost($obj, 'removeStudyPermission', [
			'accountName' => $this->accountName,
			'study_id' => $this->studyId,
			'permission' => 'read'
		]);
		
		$this->assertDataMockFromPost($obj, 'removeStudyPermission', [
			'accountName' => $this->accountName,
			'study_id' => $this->studyId,
			'permission' => 'publish'
		]);
		
		$this->assertDataMockFromPost($obj, 'removeStudyPermission', [
			'accountName' => $this->accountName,
			'study_id' => $this->studyId,
			'permission' => 'msg'
		]);
		
		$_POST['permission'] = 'write';
		$obj->exec();
		$this->assertDataMock('removeStudyPermission',
			[$this->accountName, $this->studyId, 'write'],
			[$this->accountName, $this->studyId, 'publish']
		);
		
		$_POST['permission'] = 'faulty';
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
	
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(DeleteAccountPermission::class, [
			'accountName' => 'accountName',
			'permission' => 'write',
			'studyId' => 123
		]);
	}
}