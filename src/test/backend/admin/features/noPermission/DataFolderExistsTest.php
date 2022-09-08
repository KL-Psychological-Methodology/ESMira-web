<?php

namespace test\backend\admin\features\noPermission;

use backend\admin\features\noPermission\DataFolderExists;
use backend\ESMiraInitializer;
use backend\PageFlowException;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseNoPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class DataFolderExistsTest extends BaseNoPermissionTestSetup {
	private $infoContent = ['entry1', 'entry2'];
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getESMiraInitializer',
			$this->createDataMock(ESMiraInitializer::class, 'getInfoArray', $this->infoContent),
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$this->isInit = false;
		$path = 'any/path';
		$this->setPost([
			'data_location' => $path
		]);
		$obj = new DataFolderExists();
		$this->assertEquals($this->infoContent, $obj->exec());
		$this->assertDataMock('getInfoArray', [$path]);
	}
	
	function test_afterInit() {
		$this->isInit = true;
		$path = 'any/path';
		$this->setPost([
			'data_location' => $path
		]);
		$obj = new DataFolderExists();
		$this->expectException(PageFlowException::class);
		$this->expectErrorMessage('Disabled');
		$obj->exec();
	}
	
	function test_with_missing_data() {
		$this->isInit = false;
		$this->assertMissingDataForFeatureObj(DataFolderExists::class, [
			'data_location' => 'path',
		]);
	}
}