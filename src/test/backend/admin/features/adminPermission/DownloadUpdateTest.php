<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\DownloadUpdate;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\FileSystemBasics;
use backend\exceptions\PageFlowException;
use backend\Paths;
use Exception;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';
require_once __DIR__ .'/../../../../testConfigs/variables.php';

class DownloadUpdateTest extends BaseAdminPermissionTestSetup {
	private $zipFileContent = 'definitely zipped data';
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if(!file_exists(TEST_DATA_FOLDER))
			mkdir(TEST_DATA_FOLDER);
	}
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		FileSystemBasics::emptyFolder(TEST_DATA_FOLDER);
		rmdir(TEST_DATA_FOLDER);

		$pathUpdate = Paths::FILE_SERVER_UPDATE;
		if(file_exists($pathUpdate))
			unlink($pathUpdate);
	}
	
	function test() {
		$path = TEST_DATA_FOLDER .'release.zip';
		file_put_contents($path, $this->zipFileContent);
		
		$this->setPost([
			'url' => $path
		]);
		$obj = new DownloadUpdate();
		$obj->exec();
		$this->assertEquals($this->zipFileContent, file_get_contents(Paths::FILE_SERVER_UPDATE));
	}
}