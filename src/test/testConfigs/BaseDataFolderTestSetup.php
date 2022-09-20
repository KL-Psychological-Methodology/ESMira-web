<?php

namespace test\testConfigs;

use backend\admin\features\adminPermission\AddAccountPermission;
use backend\admin\features\noPermission\Login;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\fileSystem\ESMiraInitializerFS;
use backend\FileSystemBasics;
use backend\exceptions\PageFlowException;
use backend\Permission;
use PHPUnit\Framework\TestCase;
use stdClass;

require_once __DIR__ . '/../../backend/autoload.php';

abstract class BaseDataFolderTestSetup extends BaseTestSetup {
	protected static $accountName = 'user1';
	protected static $password = 'pass1';
	
	static function createEsmiraFolder(string $accountName, string $password) {
		Configs::resetAll();
		Configs::injectConfig('configs.dataFolder.injected.php');
		if(!file_exists(TEST_DATA_FOLDER))
			mkdir(TEST_DATA_FOLDER);
		$initializer = new ESMiraInitializerFS();
		$initializer->create($accountName, $password);
	}
	
	static function setUpBeforeClass(): void {
		$_SESSION = [];
		self::createEsmiraFolder(self::$accountName, self::$password);
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
	 * @throws \backend\exceptions\PageFlowException
	 * @throws \backend\exceptions\CriticalException
	 */
	protected function login($accountName = null, $password = null) {
		Permission::setLoggedIn($accountName ?? self::$accountName);
	}
	protected function addPermission(string $permissionName, int $studyId, $accountName = null) {
		$accountStore = Configs::getDataStore()->getAccountStore();
		$accountStore->addStudyPermission($accountName ?? self::$accountName, $studyId, $permissionName);
	}
}