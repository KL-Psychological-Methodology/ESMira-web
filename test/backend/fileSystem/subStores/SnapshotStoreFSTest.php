<?php

namespace backend\fileSystem\subStores;

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\Paths;
use Error;
use testConfigs\BaseTestSetup;
use ZipArchive;

require_once __DIR__ . '/../../../autoload.php';
require_once __DIR__ .'/../../../testConfigs/variables.php';

class SnapshotStoreFSTest extends BaseTestSetup {
	private string $pathBackup = TEST_DATA_FOLDER . 'backup/';
	private string $pathUpdate = TEST_DATA_FOLDER . 'update/';
	private string $pathData = TEST_DATA_FOLDER . PathsFS::FILENAME_DATA . '/';
	private string $pathSnapshots = TEST_DATA_FOLDER . PathsFS::FILENAME_DATA . '/snapshots/';
	
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		Configs::injectConfig('configs.dataFolder.injected.php');
		mkdir(TEST_DATA_FOLDER . PathsFS::FILENAME_DATA, 0777, true);
	}
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		Configs::resetAll();
		FileSystemBasics::emptyFolder(TEST_DATA_FOLDER);
		rmdir(TEST_DATA_FOLDER);
	}
	
	protected function tearDown(): void {
		parent::tearDown();
		if(file_exists($this->pathUpdate)) {
			FileSystemBasics::emptyFolder($this->pathUpdate);
			rmdir($this->pathUpdate);
		}
		if(file_exists($this->pathBackup)) {
			FileSystemBasics::emptyFolder($this->pathBackup);
			rmdir($this->pathBackup);
		}
		if(file_exists($this->pathData)) {
			FileSystemBasics::emptyFolder($this->pathData);
		}
		if(file_exists($this->pathSnapshots)) {
			FileSystemBasics::emptyFolder($this->pathSnapshots);
			rmdir($this->pathSnapshots);
		}
	}
	
	/**
	 * Creates the following structure:
	 * - esmira_data/original1.txt
	 * - esmira_data/original2.txt
	 * - esmira_data/folder/original3.txt
	 *
	 * - update/update1.txt
	 * - update/update2.txt
	 * - update/update3.txt
	 * - update/folder/update4.txt
	 * - update/folder/update5.txt
	 */
	private function createFolderContent() {
		// Fill data folder:
		file_put_contents($this->pathData . '/original1.txt', 'old data');
		file_put_contents($this->pathData . '/original2.txt', 'old data');
		if(!file_exists($this->pathData .'/folder')) {
			mkdir($this->pathData . '/folder', 0777, true);
		}
		file_put_contents($this->pathData . '/folder/original3.txt', 'old data');
		
		// Fill update folder:
		if(!file_exists($this->pathUpdate . PathsFS::FILENAME_DATA)) {
			mkdir($this->pathUpdate . PathsFS::FILENAME_DATA, 0777, true);
		}
		file_put_contents($this->pathUpdate . PathsFS::FILENAME_DATA . '/update1.txt', 'new data');
		file_put_contents($this->pathUpdate . PathsFS::FILENAME_DATA . '/update2.txt', 'new data');
		file_put_contents($this->pathUpdate . PathsFS::FILENAME_DATA . '/update3.txt', 'new data');
		if(!file_exists($this->pathUpdate . PathsFS::FILENAME_DATA .'/folder')) {
			mkdir($this->pathUpdate . PathsFS::FILENAME_DATA . '/folder', 0777, true);
		}
		file_put_contents($this->pathUpdate . PathsFS::FILENAME_DATA . '/folder/update4.txt', 'new data');
		file_put_contents($this->pathUpdate . PathsFS::FILENAME_DATA . '/folder/update5.txt', 'new data');
	}
	
	
	public function test_addDataToZip() {
		file_put_contents(TEST_DATA_FOLDER . '/' . PathsFS::FILENAME_DATA . '/file1.txt', 'test1');
		file_put_contents(TEST_DATA_FOLDER . '/' . PathsFS::FILENAME_DATA . '/file2.txt', 'test2');
		mkdir(TEST_DATA_FOLDER . '/' . PathsFS::FILENAME_DATA . '/folder', 0777, true);
		file_put_contents(TEST_DATA_FOLDER . '/' . PathsFS::FILENAME_DATA . '/folder/file3.txt', 'test3');
		file_put_contents(TEST_DATA_FOLDER . '/' . PathsFS::FILENAME_DATA . '/folder/file4.txt', 'test4');
		
		$zip = new ZipArchive();
		$zip->open(TEST_DATA_FOLDER .'test.zip', ZIPARCHIVE::CREATE);
		
		// Setup:
		$i = 0;
		$snapshotStore = new SnapshotStoreFS();
		$snapshotStore->addDataToZip($zip, function(int $current, int $total) use(&$i) {
			$this->assertEquals(++$i, $current);
			$this->assertEquals(4, $total);
		});
		$zip->close();
		
		
		// Asserts:
		$testZip = new ZipArchive();
		$testZip->open(TEST_DATA_FOLDER .'test.zip');
		
		$this->assertEquals('test1', $testZip->getFromName(PathsFS::FILENAME_DATA .'/file1.txt'));
		$this->assertEquals('test2', $testZip->getFromName(PathsFS::FILENAME_DATA .'/file2.txt'));
		$this->assertEquals('test3', $testZip->getFromName(PathsFS::FILENAME_DATA .'/folder/file3.txt'));
		$this->assertEquals('test4', $testZip->getFromName(PathsFS::FILENAME_DATA .'/folder/file4.txt'));
		
		
		$testZip->close();
	}
	
	public function test_restoreDataFromSnapshot() {
		$this->createFolderContent();
		
		$expectedFeedback = [
			[1, 2, 0, 1],
			[1, 2, 1, 3],
			[1, 2, 2, 3],
			[1, 2, 3, 3],
			
			[2, 2, 0, 1],
			[2, 2, 1, 5],
			[2, 2, 2, 5],
			[2, 2, 3, 5],
			[2, 2, 4, 5],
			[2, 2, 5, 5],
		];
		$i = 0;
		$progressError = false;
		
		$snapshotStore = new SnapshotStoreFS();
		$snapshotStore->restoreDataFromSnapshot($this->pathUpdate, $this->pathBackup,
			function(int $currentStage, int $maxStages, int $step, int $total) use(&$i, $expectedFeedback, &$progressError) {
				// we should not trow in here because it would be caught by restoreDataFromSnapshot() restoreDataFromSnapshot which changes the exception
				if(!$progressError && $expectedFeedback[$i] != [$currentStage, $maxStages, $step, $total]) {
					$progressError = [$expectedFeedback[$i], [$currentStage, $maxStages, $step, $total]];
				}
				++$i;
			});
		
		if($progressError) {
			$this->assertEquals($progressError[0], $progressError[1]);
		}
		
		// Assert:
		$this->assertFileDoesNotExist($this->pathData . '/original1.txt');
		$this->assertFileDoesNotExist($this->pathData . '/original2.txt');
		$this->assertFileDoesNotExist($this->pathData . '/folder/original3.txt');
		$this->assertFileExists($this->pathData . '/update1.txt');
		$this->assertFileExists($this->pathData . '/update2.txt');
		$this->assertFileExists($this->pathData . '/update3.txt');
		$this->assertFileExists($this->pathData . '/folder/update4.txt');
		$this->assertFileExists($this->pathData . '/folder/update5.txt');
		
		$this->assertFileDoesNotExist($this->pathUpdate . PathsFS::FILENAME_DATA); // Ensure update folder is cleaned up
		$this->assertFileDoesNotExist($this->pathBackup . PathsFS::FILENAME_DATA); // Ensure backup folder is cleaned up
	}
	
	public function test_restoreDataFromSnapshot_error_when_moving_to_backup() {
		$this->createFolderContent();
		
		$snapshotStore = new SnapshotStoreFS();
		$errorMsg = "";
		try {
			$snapshotStore->restoreDataFromSnapshot($this->pathUpdate, $this->pathBackup,
				function(int $currentStage, int $maxStages, int $step, int $total) {
					if($currentStage == 1 && $step == 3) {
						throw new Error('Test Exception when moving to backup');
					}
				});
		}
		catch(CriticalException $e) {
			$errorMsg = $e->getMessage();
		}
		$this->assertStringContainsString('Could not move files to backup location', $errorMsg);
		
		$this->assertFileExists($this->pathData . '/original1.txt');
		$this->assertFileExists($this->pathData . '/original2.txt');
		$this->assertFileExists($this->pathData . '/folder/original3.txt');
	}
	
	public function test_restoreDataFromSnapshot_error_when_restoring_files() {
		$this->createFolderContent();
		
		$snapshotStore = new SnapshotStoreFS();
		$errorMsg = "";
		try {
			$snapshotStore->restoreDataFromSnapshot($this->pathUpdate, $this->pathBackup,
				function(int $currentStage, int $maxStages, int $step, int $total) {
					if($currentStage == 2 && $step == 3) {
						throw new Error('Test Exception when moving to backup');
					}
				});
		}
		catch(CriticalException $e) {
			$errorMsg = $e->getMessage();
		}
		$this->assertStringContainsString('Could not move update', $errorMsg);
		
		$this->assertFileExists($this->pathData . '/original1.txt');
		$this->assertFileExists($this->pathData . '/original2.txt');
		$this->assertFileExists($this->pathData . '/folder/original3.txt');
	}
	
	public function test_listSnapshots_noSnapshots() {
		// Arrange:
		mkdir($this->pathSnapshots, 0777, true);
		
		// Act:
		$snapshotStore = new SnapshotStoreFS();
		$snapshots = $snapshotStore->listSnapshots();
		
		// Assert:
		$this->assertEquals([], $snapshots);
	}
	
	public function test_listSnapshots_oneSnapshot() {
		// Arrange:
		mkdir($this->pathSnapshots, 0777, true);
		$snapshotPath = $this->pathSnapshots . Paths::makeUrlFriendly('snapshot1') .'.zip';
		file_put_contents($snapshotPath, 'snapshot content');
		touch($snapshotPath, 1672531200); // Unix timestamp for consistent created time
		
		// Act:
		$snapshotStore = new SnapshotStoreFS();
		$snapshots = $snapshotStore->listSnapshots();
		
		// Assert:
		$this->assertCount(1, $snapshots);
		$this->assertEquals('snapshot1', $snapshots[0]['name']);
		$this->assertEquals(1672531200, $snapshots[0]['created']);
		$this->assertEquals(filesize($snapshotPath), $snapshots[0]['size']);
	}
	
	public function test_listSnapshots_multipleSnapshots() {
		// Arrange:
		mkdir($this->pathSnapshots, 0777, true);
		$snapshotsData = [
			['name' => 'snapshot1', 'created' => 1672531200],
			['name' => 'snapshot2', 'created' => 1682531200],
			['name' => 'snapshot3', 'created' => 1692531200],
		];
		
		foreach($snapshotsData as $snapshot) {
			$snapshotPath = $this->pathSnapshots . Paths::makeUrlFriendly($snapshot['name']) . '.zip';
			file_put_contents($snapshotPath, 'snapshot content');
			touch($snapshotPath, $snapshot['created']);
		}
		
		// Act:
		$snapshotStore = new SnapshotStoreFS();
		$snapshots = $snapshotStore->listSnapshots();
		
		// Assert:
		$this->assertCount(3, $snapshots);
		foreach($snapshotsData as $index => $snapshotData) {
			$this->assertEquals($snapshotData['name'], $snapshots[$index]['name']);
			$this->assertEquals($snapshotData['created'], $snapshots[$index]['created']);
			$snapshotPath = $this->pathSnapshots . Paths::makeUrlFriendly($snapshot['name']) . '.zip';
			$this->assertEquals(filesize($snapshotPath), $snapshots[$index]['size']);
		}
	}
}