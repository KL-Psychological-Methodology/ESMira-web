<?php

namespace test\backend\admin\features\writePermission;

use backend\admin\features\writePermission\CheckChanged;
use backend\subStores\StudyStore;
use PHPUnit\Framework\MockObject\Stub;
use stdClass;
use test\testConfigs\BaseWritePermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

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
		$this->setGet([
			'lastChanged' => $this->lastChanged
		]);
		$obj = new CheckChanged();
		
		self::assertEquals(['lastChanged' => $this->lastChanged], $obj->exec());
		$this->assertDataMock('getStudyLastChanged', [$this->studyId]);
	}
	
	function test_outdated() {
		$this->setGet([
			'lastChanged' => $this->lastChanged - 1
		]);
		$obj = new CheckChanged();
		
		self::assertEquals(['lastChanged' => $this->lastChanged, 'json' => $this->config], $obj->exec());
		$this->assertDataMock('getStudyLastChanged', [$this->studyId]);
		$this->assertDataMock('getStudyConfig', [$this->studyId]);
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(CheckChanged::class, [
			'lastChanged' => 'lastChanged'
		], true);
	}
}