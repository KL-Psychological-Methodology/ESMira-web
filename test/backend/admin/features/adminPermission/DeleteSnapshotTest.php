<?php

namespace backend\admin\features\adminPermission;

use backend\Configs;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\Paths;
use testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../autoload.php';
require_once __DIR__ .'/../../../../testConfigs/variables.php';

class DeleteSnapshotTest extends BaseAdminPermissionTestSetup {
	
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		Configs::injectConfig('configs.dataFolder.injected.php');
		mkdir(TEST_DATA_FOLDER . '/' . PathsFS::FILENAME_DATA, 0777, true);
	}
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		Configs::resetAll();
		FileSystemBasics::emptyFolder(TEST_DATA_FOLDER);
		rmdir(TEST_DATA_FOLDER);
	}
	
	public function testExecDeletesSnapshotFileSuccessfully(): void {
		$snapshotsPath = TEST_DATA_FOLDER . PathsFS::FILENAME_DATA . '/snapshots/';
		$snapshotName = 'testSnapshot';
		$snapshotZipPath = $snapshotsPath . Paths::makeUrlFriendly($snapshotName) .'.zip';
		
		mkdir($snapshotsPath, 0777, true);
		file_put_contents($snapshotZipPath, 'snapshot content');
		
		// Simulate $_POST input
		$_POST['snapshotName'] = $snapshotName;
		
		$deleteSnapshot = new DeleteSnapshot();
		$deleteSnapshot->exec();
		
		$this->assertFileDoesNotExist($snapshotZipPath);
	}
}