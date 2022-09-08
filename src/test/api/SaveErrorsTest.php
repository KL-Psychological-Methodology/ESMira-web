<?php

namespace test\api;

use backend\JsonOutput;
use backend\Main;
use backend\subStores\ErrorReportStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseApiTestSetup;

require_once __DIR__ .'/../../backend/autoload.php';

class SaveErrorsTest extends BaseApiTestSetup {
	private $success = true;
	
	public function setUp(): void {
		parent::setUp();
		$this->success = true;
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$errorReportStore = $this->createDataMock(ErrorReportStore::class, 'saveErrorReport', function() {return $this->success;});
		$this->createStoreMock('getErrorReportStore', $errorReportStore, $observer);
		
		return $observer;
	}
	
	function test() {
		Main::$defaultPostInput = 'errorReport';
		require DIR_BASE .'/api/save_errors.php';
		$this->assertDataMock('saveErrorReport', ['errorReport']);
		$this->expectOutputString(JsonOutput::successObj());
	}
	
	function test_when_saving_fails() {
		$this->success = false;
		Main::$defaultPostInput = 'errorReport';
		require DIR_BASE .'/api/save_errors.php';
		$this->assertDataMock('saveErrorReport', ['errorReport']);
		$this->expectOutputString(JsonOutput::error('Could not save report'));
	}
	
	function test_with_missing_data() {
		Main::$defaultPostInput = '';
		$this->expectOutputString(JsonOutput::error('no data'));
		require DIR_BASE .'/api/save_errors.php';
	}
	
	function test_without_init() {
		$this->assertIsInit('save_errors');
	}
}