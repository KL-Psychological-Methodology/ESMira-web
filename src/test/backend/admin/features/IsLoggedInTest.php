<?php

namespace backend\admin\features;

use backend\admin\IsLoggedIn;
use backend\Permission;
use test\testConfigs\BaseLoggedInPermissionTestSetup;

require_once __DIR__ . '/../../../../backend/autoload.php';

class IsLoggedInTest extends BaseLoggedInPermissionTestSetup {
	private function createObj(): IsLoggedIn {
		return new class extends IsLoggedIn {
			function getIsAdmin(): bool {
				return $this->isAdmin;
			}
			function getStudyId(): int {
				return $this->studyId;
			}
			function exec(): array {
				return [];
			}
		};
	}
	
	public function test_studyId_from_get() {
		$this->setPost();
		$this->setGet([
			'study_id' => 123456
		]);
		$obj = $this->createObj();
		$this->assertEquals(123456, $obj->getStudyId());
	}
	public function test_studyId_from_post() {
		$this->setPost([
			'study_id' => 123456
		]);
		$obj = $this->createObj();
		$this->assertEquals(123456, $obj->getStudyId());
	}
	public function test_without_studyId() {
		$this->setPost();
		$obj = $this->createObj();
		$this->assertEquals(0, $obj->getStudyId());
	}
	public function test_when_logged_in() {
		$obj = $this->createObj();
		$this->assertFalse($obj->getIsAdmin());
	}
	public function test_log_in_from_post() {
		$_SERVER['REMOTE_ADDR'] = '0.0.0.0';
		$_SERVER['HTTP_USER_AGENT'] = 'UnitTester';
		$this->setPost([
			'user' => 123456,
			'pass' => 123456
		]);
		$obj = $this->createObj();
		$this->assertFalse($obj->getIsAdmin());
	}
	public function test_when_logged_out() {
		$this->setPost();
		Permission::setLoggedOut();
		$this->expectErrorMessage('No permission');
		$this->createObj();
	}
	public function test_is_not_init() {
		$this->isInit = false;
		$this->expectErrorMessage('No permission');
		$this->createObj();
	}
}