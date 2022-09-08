<?php

namespace test\backend\admin\features\readPermission;

use backend\admin\features\readPermission\CreateMediaZip;
use backend\Configs;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\Paths;
use backend\subStores\ResponsesStore;
use backend\subStores\ServerStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseReadPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class CreateMediaZipTest extends BaseReadPermissionTestSetup {
	protected $zipContent = 'content';
	
	public function setUp(): void {
		parent::setUp();
		Configs::injectConfig('configs.dataFolder.injected.php');
	}
	
	protected function tearDown(): void {
		parent::tearDown();
		FileSystemBasics::emptyFolder(TEST_DATA_FOLDER);
	}
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		rmdir(TEST_DATA_FOLDER);
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$store = $this->createMock(ResponsesStore::class);
		$store->method('createMediaZip')
			->willReturnCallback(function() {
				$this->createZip();
			});
		$this->createStoreMock('getResponsesStore', $store, $observer);
		
		$store = $this->createMock(ServerStore::class);
		$store->method('getMediaFolderPath')
			->willReturnCallback(function(int $studyId) {
				$folder = TEST_DATA_FOLDER ."$studyId/";
				if(!file_exists($folder))
					mkdir($folder, 0777, true);
				return $folder;
			});
		$this->createStoreMock('getServerStore', $store, $observer);
		
		return $observer;
	}
	
	private function createZip($content = null) {
		$folder = PathsFS::folderMedia($this->studyId);
		if(!file_exists($folder))
			mkdir($folder, 0777, true);
		file_put_contents(Paths::fileMediaZip($this->studyId), $content ?? $this->zipContent);
	}
	
	function test() {
		$obj = new CreateMediaZip();
		
		$obj->execAndOutput();
		$this->expectOutputString($this->zipContent);
	}
	function test_already_existing_zip() {
		$this->createZip('otherContent');
		$obj = new CreateMediaZip();
		
		$obj->execAndOutput();
		$this->expectOutputString('otherContent');
	}
}