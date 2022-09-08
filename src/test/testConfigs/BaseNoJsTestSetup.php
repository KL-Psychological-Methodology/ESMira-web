<?php

namespace test\testConfigs;

use backend\CriticalError;
use backend\Main;
use backend\subStores\StudyAccessIndexStore;
use backend\subStores\StudyStore;
use test\testConfigs\BaseMockedTestSetup;
use backend\JsonOutput;
use PHPUnit\Framework\MockObject\Stub;

require_once __DIR__ . '/../../backend/autoload.php';

abstract class BaseNoJsTestSetup extends BaseMockedTestSetup {
	protected $configs = [
		123 => ['id' => 123, 'title' => 'study1'],
		234 => ['id' => 234, 'title' => 'study2'],
		345 => ['id' => 345, 'title' => 'study3'],
	];
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		$observer
			->method('isInit')
			->willReturn(true);
		return $observer;
	}
	
	protected function setUpForStudyData(Stub $observer, Stub $studyStore = null, Stub $studyAccessIndexStore = null) {
		$this->setGet(['id' => 123]);
		
		if(!$studyStore)
			$studyStore = $this->createStub(StudyStore::class);
		$studyStore->method('getStudyLangConfig')
			->willReturnCallback(function(int $studyId): \stdClass {
				return json_decode(json_encode($this->configs[$studyId]));
			});
		$this->createStoreMock('getStudyStore', $studyStore, $observer);
		
		
		if(!$studyAccessIndexStore)
			$studyAccessIndexStore = $this->createStub(StudyAccessIndexStore::class);
		$studyAccessIndexStore->method('getStudyIds')
			->willReturnCallback(function(): array {
				return array_keys($this->configs); //we are not testing access keys. So we are just returning everything
			});
		
		
		$this->createStoreMock('getStudyAccessIndexStore', $studyAccessIndexStore, $observer);
	}
}