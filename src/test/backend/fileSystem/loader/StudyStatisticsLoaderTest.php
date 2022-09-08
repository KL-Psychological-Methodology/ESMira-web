<?php

namespace test\backend\fileSystem\loader;

use backend\Configs;
use backend\fileSystem\loader\StudyStatisticsLoader;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class StudyStatisticsLoaderTest extends BaseDataFolderTestSetup {
	private $studyId = 123;
	
	protected function setUp(): void {
		parent::setUp();
		$this->createEmptyStudy($this->studyId);
	}
	protected function tearDown(): void {
		parent::tearDown();
		Configs::getDataStore()->getStudyStore()->delete($this->studyId);
	}
	
	function test_import_and_export() {
		$studyId = $this->studyId;
		$source = (object) ['test' => 456456];
		
		$this->createEmptyStudy($studyId);
		
		StudyStatisticsLoader::exportFile($studyId, $source);
		
		$exported = StudyStatisticsLoader::importFile($studyId);
		
		$this->assertEquals($source->test, $exported->test);
	}
	
	
	function test_import_and_export_with_keepOpen() {
		$studyId = $this->studyId;
		$source = (object) ['test' => 456456];
		
		//file does not exist:
		$this->assertEquals((object) [], StudyStatisticsLoader::importFile($studyId, true));
		StudyStatisticsLoader::exportFile($studyId, $source);
		
		//file already exists:
		$this->assertEquals($source, StudyStatisticsLoader::importFile($studyId, true));
		
		StudyStatisticsLoader::exportFile($studyId, (object) []);
		$this->assertEquals((object) [], StudyStatisticsLoader::importFile($studyId));
	}
}