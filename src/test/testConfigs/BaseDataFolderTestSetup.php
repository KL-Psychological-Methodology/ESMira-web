<?php

namespace test\testConfigs;

use backend\admin\features\adminPermission\AddUserPermission;
use backend\admin\features\noPermission\Login;
use backend\Configs;
use backend\CriticalError;
use backend\fileSystem\ESMiraInitializerFS;
use backend\FileSystemBasics;
use backend\PageFlowException;
use backend\Permission;
use PHPUnit\Framework\TestCase;
use stdClass;

require_once __DIR__ . '/../../backend/autoload.php';

abstract class BaseDataFolderTestSetup extends BaseTestSetup {
	protected static $username = 'user1';
	protected static $password = 'pass1';
	
	static function createEsmiraFolder(string $username, string $password) {
		Configs::resetAll();
		Configs::injectConfig('configs.dataFolder.injected.php');
		if(!file_exists(TEST_DATA_FOLDER))
			mkdir(TEST_DATA_FOLDER);
		$initializer = new ESMiraInitializerFS();
		$initializer->create($username, $password);
	}
	
	static function setUpBeforeClass(): void {
		$_SESSION = [];
		self::createEsmiraFolder(self::$username, self::$password);
	}
	static function tearDownAfterClass(): void {
		if(file_exists(TEST_DATA_FOLDER)) {
			FileSystemBasics::emptyFolder(TEST_DATA_FOLDER);
			rmdir(TEST_DATA_FOLDER);
		}
		Configs::resetAll();
	}
	
	protected function createEmptyStudy($studyId) {
		$this->createStudy((object) ['id' => $studyId]);
	}
	protected function createStudy(stdClass $studyArray, array $questionnaireKeys = []) {
		$studySaver = Configs::getDataStore()->getStudyStore();
		$studySaver->saveStudy((object) ['_' => (object) $studyArray], $questionnaireKeys);
	}
	
	/**
	 * @throws PageFlowException
	 * @throws CriticalError
	 */
	protected function login($username = null, $password = null) {
//		$_SERVER['REMOTE_ADDR'] = '0.0.0.0';
//		$_SERVER['HTTP_USER_AGENT'] = 'UnitTester';
//		$_POST['user'] = $username ?? self::$username;
//		$_POST['pass'] = $password ?? self::$password;
//		$login = new Login();
//		$login->exec();
		Permission::setLoggedIn($username ?? self::$username);
	}
	protected function addPermission(string $permissionName, int $studyId, $username = null) {
		$userStore = Configs::getDataStore()->getUserStore();
		$userStore->addStudyPermission($username ?? self::$username, $studyId, $permissionName);
	}
}