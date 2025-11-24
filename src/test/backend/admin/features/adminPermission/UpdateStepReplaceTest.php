<?php

namespace backend\admin\features\adminPermission;

use backend\exceptions\CriticalException;
use backend\FileSystemBasics;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';
require_once __DIR__ .'/../../../../testConfigs/variables.php';

class UpdateStepReplaceTest extends BaseAdminPermissionTestSetup {
	private string $pathStructureFile = TEST_DATA_FOLDER .'home/STRUCTURE';
	private string $pathHome = TEST_DATA_FOLDER .'home/';
	private string $pathUpdate = TEST_DATA_FOLDER .'update/';
	private string $pathBackup = TEST_DATA_FOLDER .'backup/';
	
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
	
	public function setUp(): void {
		parent::setUp();
		
		//prepare home folder:
		mkdir($this->pathHome);
		file_put_contents($this->pathStructureFile, '["STRUCTURE", "replace.txt", "original.txt", "folder1/"]');
		file_put_contents($this->pathHome .'shouldStay.txt', 'contentOriginal');
		file_put_contents($this->pathHome .'replace.txt', 'contentOriginal');
		file_put_contents($this->pathHome .'original.txt', 'contentOriginal');
		mkdir($this->pathHome .'folder1/');
		file_put_contents($this->pathHome .'folder1/sub1.txt', 'contentOriginal');
		
		//prepare update folder:
		mkdir($this->pathUpdate);
		file_put_contents($this->pathUpdate .'replace.txt', 'contentNew');
		file_put_contents($this->pathUpdate .'update.txt', 'contentNew');
		mkdir($this->pathHome .'folder2/');
		file_put_contents($this->pathHome .'folder2/sub2.txt', 'contentNew');
	}
	
	public function tearDown(): void {
		parent::tearDown();
		if(file_exists($this->pathUpdate)) {
			FileSystemBasics::emptyFolder($this->pathUpdate);
			rmdir($this->pathUpdate);
		}
		if(file_exists($this->pathHome)) {
			FileSystemBasics::emptyFolder($this->pathHome);
			rmdir($this->pathHome);
		}
		if(file_exists($this->pathBackup)) {
			FileSystemBasics::emptyFolder($this->pathBackup);
			rmdir($this->pathBackup);
		}
	}
	
	function assertInitialState() {
		$this->assertFileExists($this->pathHome .'shouldStay.txt');
		$this->assertFileExists($this->pathHome .'folder1/sub1.txt');
		$this->assertFileExists($this->pathHome .'original.txt');
		$this->assertEquals('contentOriginal', file_get_contents($this->pathHome .'replace.txt'));
	}
	
	function runClass() {
		$obj = new UpdateStepReplace($this->pathStructureFile, $this->pathHome, $this->pathUpdate, $this->pathBackup);
		$obj->exec();
	}
	
	function test() {
		$this->runClass();
		
		$this->assertFileExists($this->pathHome .'shouldStay.txt');
		$this->assertFileExists($this->pathHome .'update.txt');
		$this->assertFileExists($this->pathHome .'folder2/sub2.txt');
		$this->assertFileDoesNotExist($this->pathHome .'original.txt');
		$this->assertFileDoesNotExist($this->pathHome .'folder1/sub1.txt');
		$this->assertEquals('contentNew', file_get_contents($this->pathHome .'replace.txt'));
		
		$this->assertFileDoesNotExist($this->pathBackup .'shouldStay.txt');
		$this->assertFileExists($this->pathBackup .'folder1/sub1.txt');
		$this->assertFileExists($this->pathBackup .'original.txt');
		$this->assertEquals('contentOriginal', file_get_contents($this->pathBackup .'replace.txt'));
	}
	
	function test_when_update_does_not_exist() {
		FileSystemBasics::emptyFolder($this->pathUpdate);
		rmdir($this->pathUpdate);
		
		$this->assertException(
			function() {$this->runClass();},
			CriticalException::class,
			function($message) {$this->assertEquals("Could not find update at $this->pathUpdate", $message);}
		);
	}
	
	function test_when_backup_already_exists() {
		FileSystemBasics::createFolder($this->pathBackup);
		
		$this->assertException(
			function() {$this->runClass();},
			CriticalException::class,
			function($message) {$this->assertEquals("$this->pathBackup already exists!", $message);}
		);
	}
	
	function test_when_structure_file_does_not_exist() {
		unlink($this->pathStructureFile);
		
		$this->assertException(
			function() {$this->runClass();},
			CriticalException::class,
			function($message) {$this->assertStringEndsWith('No such file or directory', $message);}
		);
		
		$this->assertInitialState();
		$this->assertFileDoesNotExist($this->pathUpdate);
		$this->assertFileDoesNotExist($this->pathBackup);
	}
	
	function test_when_structure_file_is_faulty() {
		file_put_contents($this->pathStructureFile, '["doesNotExist.txt", "original.txt", "folder/"]');
		
		$this->assertException(
			function() {$this->runClass();},
			CriticalException::class,
			function($message) {$this->assertStringEndsWith('doesNotExist.txt does not exist, but it should!', $message);}
		);
		
		$this->assertInitialState();
		$this->assertFileDoesNotExist($this->pathUpdate);
		$this->assertFileDoesNotExist($this->pathBackup);
	}
	
	function test_when_update_is_faulty() {
		file_put_contents($this->pathUpdate .'shouldStay.txt', 'content'); //This file will stay in Home, so it cannot be copied in from updates
		
		$this->assertException(
			function() {$this->runClass();},
			CriticalException::class,
			function($message) {
				$this->assertStringContainsString('shouldStay.txt already exists!', $message);
			}
		);
		
		$this->assertInitialState();
	}
}