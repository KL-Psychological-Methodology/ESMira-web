<?php

namespace backend\admin\features\adminPermission;

use backend\FileSystemBasics;
use backend\Paths;
use backend\SSE;
use testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../autoload.php';
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
		file_put_contents($this->pathStructureFile, '["STRUCTURE", "replace.txt", "original.txt", "folder1"]');
		file_put_contents($this->pathHome .'shouldStay.txt', 'contentOriginal');
		file_put_contents($this->pathHome .'replace.txt', 'contentOriginal');
		file_put_contents($this->pathHome .'original.txt', 'contentOriginal');
		mkdir($this->pathHome .'folder1/');
		file_put_contents($this->pathHome .'folder1/sub1.txt', 'contentOriginal');
		
		//prepare update folder:
		$pathServerUpdate = $this->pathUpdate . Paths::SUB_PATH_SERVER_UPDATE_FILES;
		mkdir($pathServerUpdate, 0777, true);
		file_put_contents($pathServerUpdate .'replace.txt', 'contentNew');
		file_put_contents($pathServerUpdate .'update.txt', 'contentNew');
		mkdir($pathServerUpdate .'folder2/');
		file_put_contents($pathServerUpdate .'folder2/sub2.txt', 'contentNew');
		file_put_contents($pathServerUpdate .'STRUCTURE', '["STRUCTURE", "replace.txt", "update.txt", "folder2"]');
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
		$this->assertFileDoesNotExist($this->pathUpdate);
		$this->assertFileDoesNotExist($this->pathBackup);
	}
	
	function runClass(?string $error = null) {
		$sse = $this->createMock(SSE::class);
		$sse->expects($error ? $this->once() : $this->never())
			->method('flushFailed')
			->with($error ?? $this->anything());
		$obj = new UpdateStepReplace($this->pathStructureFile, $this->pathHome, $this->pathUpdate, $this->pathBackup, $sse);
		$obj->execAndOutput();
	}
	
	function test() {
		$this->runClass();
		
		$this->assertFileExists($this->pathHome .'shouldStay.txt');
		$this->assertFileExists($this->pathHome .'update.txt');
		$this->assertFileExists($this->pathHome .'folder2/sub2.txt');
		$this->assertFileDoesNotExist($this->pathHome .'original.txt');
		$this->assertFileDoesNotExist($this->pathHome .'folder1/sub1.txt');
		$this->assertEquals('contentNew', file_get_contents($this->pathHome .'replace.txt'));
		
		$this->assertFileDoesNotExist($this->pathUpdate);
		$this->assertFileDoesNotExist($this->pathBackup);
	}
	
	function test_when_update_does_not_exist() {
		FileSystemBasics::emptyFolder($this->pathUpdate);
		rmdir($this->pathUpdate);
		
		$this->runClass("Could not find update at $this->pathUpdate" .Paths::SUB_PATH_SERVER_UPDATE_FILES);
	}
	
	function test_when_backup_already_exists() {
		FileSystemBasics::createFolder($this->pathBackup . Paths::SUB_PATH_SERVER_UPDATE_FILES, true);
		
		$this->runClass($this->pathBackup . Paths::SUB_PATH_SERVER_UPDATE_FILES . ' already exists!');
	}
	
	function test_when_structure_file_does_not_exist() {
		unlink($this->pathStructureFile);
		
		$this->runClass("$this->pathStructureFile does not exist!");
		$this->assertInitialState();
	}
	
	function test_when_structure_file_is_faulty() {
		file_put_contents($this->pathStructureFile, '["doesNotExist.txt", "original.txt", "folder/"]');
		
		$this->runClass('Could not move files to backup location. The original files have been restored. Error: ' .$this->pathHome . 'doesNotExist.txt does not exist, but it should!');
		
		$this->assertInitialState();
	}
	
	function test_when_update_is_faulty() {
		$pathServerUpdate = $this->pathUpdate . Paths::SUB_PATH_SERVER_UPDATE_FILES;
		file_put_contents($pathServerUpdate .'shouldStay.txt', 'content'); //This file will stay in Home, so it cannot be copied in from updates
		
		$this->runClass('Could not move update. The original files have been restored. Error: ' .$this->pathHome . 'shouldStay.txt already exists! Cannot move ' . $pathServerUpdate . 'shouldStay.txt');
		
		$this->assertInitialState();
	}
}