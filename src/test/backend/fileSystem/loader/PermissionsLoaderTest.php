<?php

namespace test\backend\fileSystem\loader;

use backend\fileSystem\loader\PermissionsLoader;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class PermissionsLoaderTest extends BaseDataFolderTestSetup {
	function test_import_and_export() {
		$source = ['user1' => ['write' => [123, 234]]];
		
		PermissionsLoader::exportFile($source);
		
		$this->assertEquals($source, PermissionsLoader::importFile());
		PermissionsLoader::reset();
		$this->assertEquals($source, PermissionsLoader::importFile());
	}
}