<?php

namespace backend\fileSystem\loader;

use backend\fileSystem\loader\StudyAccessKeyIndexLoader;
use testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ . '/../../../autoload.php';

class StudyAccessKeyIndexLoaderTest extends BaseDataFolderTestSetup {
	function test_import_and_export() {
		$source = ['key1' => [123, 234]];
		
		StudyAccessKeyIndexLoader::exportFile($source);
		
		$exported = StudyAccessKeyIndexLoader::importFile();
		$this->assertEquals($source, $exported);
	}
}