<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\AddUserPermission;
use test\testConfigs\BaseAdminPermissionTestSetup;
use backend\PageFlowException;
use PHPUnit\Framework\MockObject\Stub;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class AddUserPermissionTest extends BaseAdminPermissionTestSetup {
	private $username = 'user1';
	protected function setUpUserStoreObserver(): Stub {
		$observer = parent::setUpUserStoreObserver();
		
		$this->addDataMock($observer, 'addStudyPermission');
		return $observer;
	}
	
	function test() {
		$obj = new AddUserPermission();
		
		$this->assertDataMockFromPost($obj, 'addStudyPermission', [
			'user' => $this->username,
			'study_id' => $this->studyId,
			'permission' => 'read'
		]);
		
		$this->assertDataMockFromPost($obj, 'addStudyPermission', [
			'user' => $this->username,
			'study_id' => $this->studyId,
			'permission' => 'write'
		]);
		
		$this->assertDataMockFromPost($obj, 'addStudyPermission', [
			'user' => $this->username,
			'study_id' => $this->studyId,
			'permission' => 'msg'
		]);
		
		$_POST['permission'] = 'publish';
		$obj->exec();
		$this->assertDataMock('addStudyPermission',
			[$this->username, $this->studyId, 'publish'],
			[$this->username, $this->studyId, 'write']
		);
		
		$_POST['permission'] = 'faulty';
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
	
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(AddUserPermission::class, [
			'user' => 'user',
			'permission' => 'write',
			'studyId' => 123
		]);
	}
}