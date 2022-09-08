<?php

namespace test\backend\admin\features\writePermission;

use backend\admin\features\writePermission\LoadLangs;
use backend\JsonOutput;
use backend\subStores\StudyStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseWritePermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class LoadLangsTest extends BaseWritePermissionTestSetup {
	private $langJson = '{"_": {}, "de": {}}';
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getStudyStore',
			$this->createDataMock(StudyStore::class, 'getAllLangConfigsAsJson', $this->langJson),
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$obj = new LoadLangs();
		
		$obj->execAndOutput();
		$this->expectOutputString(JsonOutput::successString($this->langJson));
		$this->assertDataMock('getAllLangConfigsAsJson', [$this->studyId]);
	}
}