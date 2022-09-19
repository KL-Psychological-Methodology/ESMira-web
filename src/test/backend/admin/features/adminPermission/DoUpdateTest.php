<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\DoUpdate;
use backend\admin\features\adminPermission\DownloadUpdate;
use backend\Configs;
use backend\CriticalError;
use backend\FileSystemBasics;
use backend\PageFlowException;
use backend\Paths;
use Exception;
use PHPUnit\Framework\ExpectationFailedException;
use test\testConfigs\BaseAdminPermissionTestSetup;
use ZipArchive;

require_once __DIR__ . '/../../../../../backend/autoload.php';
require_once __DIR__ .'/../../../../testConfigs/variables.php';

/*
 * Testing this class is a bit weird and potentially dangerous because DoUpdate() moves itself and everything with it (to be then replaced by an update)
 * I dont want to mess with the source files, so make sure only mock-folders are copied. but that means some stuff can not be tested
 * like overwriting the default settings file - because nothing is actually overwritten and we would have to change const values in order to point to the mock data
 */
class DoUpdateTest extends BaseAdminPermissionTestSetup {
	private $folderPathSource = TEST_DATA_FOLDER .'source/';
	private $folderPathBackup = TEST_DATA_FOLDER .'backup/';
	private $fileUpdate = TEST_DATA_FOLDER .'update.zip';
	
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
		if(!file_exists($this->folderPathSource))
			mkdir($this->folderPathSource);
		file_put_contents($this->folderPathSource .'index.php', 'content');
		file_put_contents($this->folderPathSource .'nonEsmiraFile', 'content');
		
		if(!file_exists($this->folderPathSource .'backend/'))
			mkdir($this->folderPathSource .'backend/');
		if(!file_exists($this->folderPathSource .'backend/config/'))
			mkdir($this->folderPathSource .'backend/config/');
		file_put_contents(
			$this->folderPathSource .Paths::SUB_PATH_CONFIG,
			'<?php return ["__originalConfig" => true, "dataFolder_path" => "'.TEST_DATA_FOLDER.'"];'
		);
		
		$zip = new ZipArchive();
		$zip->open($this->fileUpdate, ZIPARCHIVE::CREATE);
		$zip->addFromString('backend/config/configs.default.php', '<?php return ["" => "__newConfig"];');
		$zip->addFromString('new File', 'content');
		$zip->close();
		$this->setGet(['fromVersion' => PHP_INT_MAX .'.0.0']); //we dont want UpdateVersion() to trigger
	}
	
	public function tearDown(): void {
		parent::tearDown();
		FileSystemBasics::emptyFolder(TEST_DATA_FOLDER);
		if(file_exists(Paths::FILE_CONFIG)) //we are working in the source - so no config file should exist here (but will be created by DoUpdate() )
			unlink(Paths::FILE_CONFIG);
	}
	
	function test() {
		$obj = new DoUpdate($this->folderPathSource, $this->folderPathBackup, $this->fileUpdate);
		$obj->exec();
		$this->assertTrue(Configs::get('__originalConfig'));
		$this->assertFileExists($this->folderPathSource .'new File');
		$this->assertFileDoesNotExist($this->folderPathSource .'index.php');
		$this->assertFileExists($this->folderPathSource .'backend/config/configs.default.php');
		$this->assertFileDoesNotExist($this->folderPathBackup);
		$this->assertFileDoesNotExist($this->fileUpdate);
	}
	
	function test_revertUpdate() {
		unlink($this->folderPathSource .Paths::SUB_PATH_CONFIG);
		$obj = new DoUpdate($this->folderPathSource, $this->folderPathBackup, $this->fileUpdate);
		try {
			$obj->exec();
		}
		catch(PageFlowException $e) {
			$this->assertFileDoesNotExist($this->folderPathSource .'new File');
			$this->assertFileExists($this->folderPathSource .'index.php');
			$this->assertFileExists($this->folderPathSource .'nonEsmiraFile');
			$this->assertFileDoesNotExist($this->folderPathBackup);
			$this->assertFileDoesNotExist($this->fileUpdate);
			return;
		}
		throw new ExpectationFailedException('exec() did not throw...');
	}
	
	function test_without_update_file() {
		unlink($this->fileUpdate);
		$this->expectErrorMessage('Could not find update');
		$obj = new DoUpdate($this->folderPathSource, $this->folderPathBackup, $this->fileUpdate);
		$obj->exec();
	}
	
	function test_with_already_exiting_update() {
		mkdir($this->folderPathBackup);
		$this->expectErrorMessage('A backup seems to already exist');
		$obj = new DoUpdate($this->folderPathSource, $this->folderPathBackup, $this->fileUpdate);
		$obj->exec();
	}
	
	function test_with_faulty_update_file() {
		file_put_contents($this->fileUpdate, 'faulty zip');
		$this->expectErrorMessage('Could not unzip update');
		$obj = new DoUpdate($this->folderPathSource, $this->folderPathBackup, $this->fileUpdate);
		$obj->exec();
	}
	
	function test_without_config_file() {
		unlink($this->folderPathSource .Paths::SUB_PATH_CONFIG);
		$this->expectErrorMessage('Could not restore settings');
		$obj = new DoUpdate($this->folderPathSource, $this->folderPathBackup, $this->fileUpdate);
		$obj->exec();
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(DoUpdate::class, [
			'fromVersion' => PHP_INT_MAX .'.0.0',
		], true);
	}
}