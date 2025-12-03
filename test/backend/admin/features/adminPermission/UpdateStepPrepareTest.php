<?php

namespace backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\UpdateStepPrepare;
use backend\exceptions\CriticalException;
use backend\FileSystemBasics;
use Exception;
use testConfigs\BaseAdminPermissionTestSetup;
use ZipArchive;

require_once __DIR__ . '/../../../../autoload.php';
require_once __DIR__ .'/../../../../testConfigs/variables.php';

/*
 * Testing this class is a bit weird and potentially dangerous because DoUpdate() moves itself and everything with it (to be then replaced by an update)
 * I dont want to mess with the source files, so make sure only mock-folders are copied. but that means some stuff can not be tested
 * like overwriting the default settings file - because nothing is actually overwritten and we would have to change const values in order to point to the mock data
 */
class UpdateStepPrepareTest extends BaseAdminPermissionTestSetup {
	private string $pathConfigFile = TEST_DATA_FOLDER .'config.php';
	private string $pathUpdate = TEST_DATA_FOLDER .'update/';
	private string $pathUpdateZip = TEST_DATA_FOLDER .'update.zip';
	
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
		
		file_put_contents($this->pathConfigFile, '<?php return ["" => "__config"];');
		
		$zip = new ZipArchive();
		$zip->open($this->pathUpdateZip, ZIPARCHIVE::CREATE);
		$zip->addEmptyDir('backend/config/');
		$zip->addFromString('new File', 'content');
		$zip->close();
		$this->setGet(['fromVersion' => PHP_INT_MAX .'.0.0']); //we dont want UpdateVersion() to trigger
	}
	
	public function tearDown(): void {
		parent::tearDown();
		if(file_exists($this->pathUpdate)) {
			FileSystemBasics::emptyFolder($this->pathUpdate);
			rmdir($this->pathUpdate);
		}
	}
	
	function runClass() {
		$obj = new UpdateStepPrepare($this->pathConfigFile, $this->pathUpdateZip, $this->pathUpdate);
		$obj->exec();
	}
	
	function test() {
		$this->runClass();
		$this->assertFileExists($this->pathUpdate .'new File');
		$this->assertFileDoesNotExist($this->pathUpdate .'backend/defaults/configs.default.php');
	}
	
	function test_when_zip_does_not_exist() {
		unlink($this->pathUpdateZip);
		
		$this->assertException(
			function() {$this->runClass();},
			CriticalException::class,
			function(string $message) {$this->assertEquals("Missing $this->pathUpdateZip", $message);}
		);
	}
	
	function test_when_update_already_exists() {
		mkdir($this->pathUpdate);
		
		$this->assertException(
			function() {$this->runClass();},
			CriticalException::class,
			function(string $message) {$this->assertEquals("$this->pathUpdate already exists. Please remove it manually and try again.", $message);}
		);
	}
	
	function test_revert() {
		mkdir($this->pathUpdate);
		file_put_contents($this->pathUpdate .'new_File', 'content');
		
		
		$this->assertException(
			function() {
				$obj = new UpdateStepPrepare($this->pathConfigFile, $this->pathUpdateZip, $this->pathUpdate);
				$obj->revert(new Exception('Mock test'));
			},
			CriticalException::class,
			function(string $message) {$this->assertEquals('Could not prepare the update because: Mock test', $message);}
		);
		
		$this->assertFileDoesNotExist($this->pathUpdate);
	}
}