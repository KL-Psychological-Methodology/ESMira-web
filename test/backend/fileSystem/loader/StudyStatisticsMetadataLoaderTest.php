<?php

namespace backend\fileSystem\loader;

use backend\fileSystem\loader\StudyStatisticsMetadataLoader;
use testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ . '/../../../autoload.php';

class StudyStatisticsMetadataLoaderTest extends BaseDataFolderTestSetup {
	function test_import_and_export() {
		$studyId = 123;
		$source = ['test' => 123];
		
		$this->createEmptyStudy($studyId);
		
		StudyStatisticsMetadataLoader::exportFile($studyId, $source);
		
		$exported = StudyStatisticsMetadataLoader::importFile($studyId);
		
		$this->assertEquals($source['test'], $exported['test']);
	}
}