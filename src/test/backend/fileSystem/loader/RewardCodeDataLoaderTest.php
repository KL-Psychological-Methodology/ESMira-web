<?php

namespace test\backend\fileSystem\loader;

use backend\dataClasses\RewardCodeData;
use backend\fileSystem\loader\RewardCodeDataLoader;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class RewardCodeDataLoaderTest extends BaseDataFolderTestSetup {
	function test_import_and_export() {
		$studyId = 123;
		$source = new RewardCodeData([123 => 2, 234 => 3, 345 => 4]);
		
		$this->createEmptyStudy($studyId);
		
		RewardCodeDataLoader::exportFile($studyId, $source);
		
		$exported = RewardCodeDataLoader::importFile($studyId, $source->code);
		$this->assertEquals($source, $exported);
	}
}