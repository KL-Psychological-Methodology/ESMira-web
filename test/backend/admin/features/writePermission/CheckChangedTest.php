<?php

namespace backend\admin\features\writePermission;

use backend\admin\features\writePermission\CheckChanged;
use backend\subStores\StudyStore;
use PHPUnit\Framework\MockObject\Stub;
use stdClass;
use testConfigs\BaseWritePermissionTestSetup;

require_once __DIR__ . '/../../../../autoload.php';

class CheckChangedTest extends BaseWritePermissionTestSetup {
	/**
	 * @var stdClass
	 */
	private $config;
	private $lastChanged = 999;
	
	public function setUp(): void {
		$this->config = (object) ['id' => 345, 'title' => 'Study'];
		parent::setUp();
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$store = $this->createDataMock(StudyStore::class, 'getStudyConfig', $this->config);
		$this->addDataMock($store, 'getStudyLastChanged', $this->lastChanged);
		$this->createStoreMock(
			'getStudyStore',
			$store,
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$obj = new CheckChanged();
		
		self::assertEquals([$this->lastChanged], $obj->exec());
		$this->assertDataMock('getStudyLastChanged', [$this->studyId]);
	}
}