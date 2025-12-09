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
		
		$this->expectExceptionMessage("Missing $this->pathUpdateZip");
		$this->runClass();
	}
	
	function test_when_update_already_exists() {
		mkdir($this->pathUpdate);
		
		$this->expectExceptionMessage("$this->pathUpdate already exists. Please manually remove it and try again.");
		$this->runClass();
	}
	
	function test_revert() {
		mkdir($this->pathUpdate);
		file_put_contents($this->pathUpdate .'new_File', 'content');
		
		
		$obj = new UpdateStepPrepare($this->pathConfigFile, $this->pathUpdateZip, $this->pathUpdate);
		try {
			$obj->revert(new Exception('Mock test'));
		}
		catch(Exception $e) {
			$this->assertEquals('Could not prepare the update because: Mock test', $e->getMessage());
			$this->assertFileDoesNotExist($this->pathUpdate);
			return;
		}
		
		throw new Error('Nothing was thrown!');
	}
}