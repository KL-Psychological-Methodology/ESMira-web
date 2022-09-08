<?php
declare(strict_types=1);

namespace test\backend\fileSystem;

require_once __DIR__ .'/../../../backend/autoload.php';
require_once __DIR__ .'/../../testConfigs/variables.php';

use backend\Configs;
use backend\CriticalError;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use test\testConfigs\BaseDataFolderTestSetup;
use test\testConfigs\BaseTestSetup;

class ESMiraInitializerFSTest extends BaseTestSetup {
	function tearDown(): void {
		Configs::resetAll();
		if(file_exists(TEST_DATA_FOLDER)) {
			FileSystemBasics::emptyFolder(TEST_DATA_FOLDER);
			rmdir(TEST_DATA_FOLDER);
		}
	}
	
	function test_getConfigAdditions_with_existing_path() {
		$initializer = Configs::getDataStore()->getESMiraInitializer();
		mkdir(TEST_DATA_FOLDER);
		
		$_POST['data_location'] = TEST_DATA_FOLDER;
		$this->assertEquals(TEST_DATA_FOLDER .PathsFS::FILENAME_DATA .'/',  $initializer->getConfigAdditions()['dataFolder_path']);
		$this->assertFalse($initializer->getInfoArray()['dataFolder_exists']);
		
		mkdir(TEST_DATA_FOLDER .PathsFS::FILENAME_DATA);
		$this->assertTrue($initializer->getInfoArray(TEST_DATA_FOLDER)['dataFolder_exists']);
	}
	
	function test_getConfigAdditions_with_faulty_path() {
		$this->expectException(CriticalError::class);
		$initializer = Configs::getDataStore()->getESMiraInitializer();
		
		$_POST['data_location'] = 'path/does/not/exist';
		$initializer->getConfigAdditions();
	}
	
	function test_create() {
		$username = 'user1';
		$password = 'pass1';
		BaseDataFolderTestSetup::createEsmiraFolder($username, $password);
		
		$userStore = Configs::getDataStore()->getUserStore();
		$this->assertTrue($userStore->doesUserExist($username));
		$this->assertTrue($userStore->checkUserLogin($username, $password));
	}
	
	function test_create_and_reuse_existing_data_folder() {
		$username1 = 'user1';
		$password1 = 'pass1';
		$username2 = 'user2';
		$password2 = 'pass2';
		
		BaseDataFolderTestSetup::createEsmiraFolder($username1, $password1);
		BaseDataFolderTestSetup::createEsmiraFolder($username2, $password2);
		
		$userStore = Configs::getDataStore()->getUserStore();
		
		$this->assertTrue($userStore->doesUserExist($username1));
		$this->assertTrue($userStore->doesUserExist($username2));
	}
	function test_create_and_move_existing_data_folder() {
		$username1 = 'user1';
		$password1 = 'pass1';
		$username2 = 'user2';
		$password2 = 'pass2';
		BaseDataFolderTestSetup::createEsmiraFolder($username1, $password1);
		
		$_POST['reuseFolder'] = true;
		BaseDataFolderTestSetup::createEsmiraFolder($username1, $password2);
		
		$userStore = Configs::getDataStore()->getUserStore();
		$this->assertTrue($userStore->doesUserExist($username1));
		$this->assertFalse($userStore->doesUserExist($username2));
	}
}