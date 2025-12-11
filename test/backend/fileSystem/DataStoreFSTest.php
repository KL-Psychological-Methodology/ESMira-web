<?php

namespace backend\fileSystem;

use backend\FileSystemBasics;
use testConfigs\BaseTestSetup;

require_once __DIR__ . '/../../autoload.php';
require_once __DIR__ .'/../../testConfigs/variables.php';

class DataStoreFSTest extends BaseTestSetup {
	
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		mkdir(TEST_DATA_FOLDER, 0777, true);
	}
	
	protected function tearDown(): void {
		FileSystemBasics::emptyFolder(TEST_DATA_FOLDER);
	}
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		rmdir(TEST_DATA_FOLDER);
	}
	
	public function test_setMaintenanceMode_and_isInMaintenanceMode() {
		$dataStore = new DataStoreFS();
		$dataStore->setMaintenanceMode(true, TEST_DATA_FOLDER);
		$this->assertTrue($dataStore->isInMaintenanceMode());
		$dataStore->setMaintenanceMode(false, TEST_DATA_FOLDER);
		$this->assertFalse($dataStore->isInMaintenanceMode());
	}
}