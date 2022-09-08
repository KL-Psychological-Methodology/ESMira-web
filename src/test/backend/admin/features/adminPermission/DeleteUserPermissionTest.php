<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\DeleteUserPermission;
use backend\PageFlowException;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class DeleteUserPermissionTest extends BaseAdminPermissionTestSetup {
	private $username = 'user1';
	protected function setUpUserStoreObserver(): Stub {
		$observer = parent::setUpUserStoreObserver();
		
		$this->addDataMock($observer, 'removeStudyPermission');
		return $observer;
	}
	
	function test() {
		$obj = new DeleteUserPermission();
		
		$this->assertDataMockFromPost($obj, 'removeStudyPermission', [
			'user' => $this->username,
			'study_id' => $this->studyId,
			'permission' => 'read'
		]);
		
		$this->assertDataMockFromPost($obj, 'removeStudyPermission', [
			'user' => $this->username,
			'study_id' => $this->studyId,
			'permission' => 'publish'
		]);
		
		$this->assertDataMockFromPost($obj, 'removeStudyPermission', [
			'user' => $this->username,
			'study_id' => $this->studyId,
			'permission' => 'msg'
		]);
		
		$_POST['permission'] = 'write';
		$obj->exec();
		$this->assertDataMock('removeStudyPermission',
			[$this->username, $this->studyId, 'write'],
			[$this->username, $this->studyId, 'publish']
		);
		
		$_POST['permission'] = 'faulty';
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
	
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(DeleteUserPermission::class, [
			'user' => 'user',
			'permission' => 'write',
			'studyId' => 123
		]);
	}
}