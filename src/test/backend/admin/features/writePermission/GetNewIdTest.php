<?php

namespace test\backend\admin\features\writePermission;

use backend\admin\features\writePermission\GetNewId;
use backend\Main;
use backend\subStores\StudyAccessIndexStore;
use backend\subStores\StudyStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseWritePermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class GetNewIdTest extends BaseWritePermissionTestSetup {
	private $studyExistsReturn = false;
	private $getStudyIdForQuestionnaireIdReturn = -1;
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$studyStore = $this->createStub(StudyStore::class);
		$studyStore->method('studyExists')
			->willReturnCallback(function() {
				return $this->studyExistsReturn;
			});
		$this->createStoreMock(
			'getStudyStore',
			$studyStore,
			$observer
		);
		
		$store = $this->createStub(StudyAccessIndexStore::class);
		$store->method('getStudyIdForQuestionnaireId')
			->willReturnCallback(function() {
				return $this->getStudyIdForQuestionnaireIdReturn;
			});
		$this->createStoreMock(
			'getStudyAccessIndexStore',
			$store,
			$observer
		);
		
		return $observer;
	}
	
	function test_for_duplicates() {
		$this->setGet(['for' => 'questionnaire']);
		$obj = new GetNewId();
		
		$usedIndex = [];
		for($i=100; $i>=0; --$i) {
			Main::$defaultPostInput = json_encode($usedIndex);
			$id = $obj->exec()[0];
			$this->assertArrayNotHasKey($id, $usedIndex);
			$usedIndex[$id] = true;
		}
	}
	function test_when_study_exists() {
		$this->studyExistsReturn = true;
		$obj = new GetNewId();
		
		$this->expectErrorMessage('Could not find an unused id');
		$obj->exec()[0];
	}
	function test_when_questionnaireId_exists() {
		$this->getStudyIdForQuestionnaireIdReturn = $this->studyId;
		$obj = new GetNewId();
		
		$this->expectErrorMessage('Could not find an unused id');
		$obj->exec()[0];
	}
}