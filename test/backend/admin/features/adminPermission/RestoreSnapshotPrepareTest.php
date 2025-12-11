<?php

namespace backend\admin\features\adminPermission;

use backend\exceptions\CriticalException;
use backend\fileSystem\subStores\SnapshotStoreFS;
use backend\FileSystemBasics;
use PHPUnit\Framework\MockObject\Stub;
use testConfigs\BaseAdminPermissionTestSetup;
use ZipArchive;

require_once __DIR__ . '/../../../../autoload.php';
require_once __DIR__ .'/../../../../testConfigs/variables.php';

class RestoreSnapshotPrepareTest extends BaseAdminPermissionTestSetup {
	private string $pathUpdate;
	
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if(!file_exists(TEST_DATA_FOLDER))
			mkdir(TEST_DATA_FOLDER);
	}
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		FileSystemBasics::emptyFolder(TEST_DATA_FOLDER);
		rmdir(TEST_DATA_FOLDER);
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$snapshotStore = $this->createMock(SnapshotStoreFS::class);
		$snapshotStore->method('getSnapshotZipPath')
			->willReturnCallback(function(string $snapshotName) {
				return TEST_DATA_FOLDER ."/$snapshotName.zip";
			});
		
		$this->createStoreMock('getSnapshotStore', $snapshotStore, $observer);
		
		return $observer;
	}
	
	public function setUp(): void {
		parent::setUp();
		$this->pathUpdate = TEST_DATA_FOLDER . '/test_update';
	}
	
	public function tearDown(): void {
		parent::tearDown();
		if(file_exists($this->pathUpdate)) {
			FileSystemBasics::emptyFolder($this->pathUpdate);
			rmdir($this->pathUpdate);
		}
	}
	
	public function test() {
		$_POST['name'] = 'snapshot1';
		
		$zip = new ZipArchive();
		$zip->open(TEST_DATA_FOLDER .'/snapshot1.zip', ZipArchive::CREATE);
		$zip->addEmptyDir('test');
		$zip->close();
		
		$restoreSnapshotPrepare = new RestoreSnapshotPrepare($this->pathUpdate);
		
		$restoreSnapshotPrepare->exec();
		
		$this->assertDirectoryExists($this->pathUpdate);
		$this->assertDirectoryExists($this->pathUpdate . '/test');
	}
	
	public function test_invalid_snapshot() {
		$this->expectException(CriticalException::class);
		
		$_POST['name'] = 'invalid_snapshot';
		
		$this->expectException(CriticalException::class);
		$restoreSnapshotPrepare = new RestoreSnapshotPrepare($this->pathUpdate);
		$restoreSnapshotPrepare->exec();
	}
}