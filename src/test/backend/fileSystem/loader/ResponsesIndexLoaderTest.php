<?php

namespace test\backend\fileSystem\loader;

use backend\exceptions\CriticalException;
use backend\fileSystem\loader\ResponsesIndexLoader;
use backend\fileSystem\PathsFS;
use backend\ResponsesIndex;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class ResponsesIndexLoaderTest extends BaseDataFolderTestSetup {
	function test_import_and_export() {
		$studyId = 123;
		$source = new ResponsesIndex(['key1', 'key2', 'key3'], ['image']);
		
		$this->createEmptyStudy($studyId);
		
		ResponsesIndexLoader::exportFile($studyId, PathsFS::FILENAME_EVENTS, $source);
		
		$exported = ResponsesIndexLoader::importFile($studyId, PathsFS::FILENAME_EVENTS);
		
		$this->assertEquals($source->keys, $exported->keys);
		$this->assertEquals($source->types, $exported->types);
	}
	
	function test_broken_import() {
		$this->expectException(CriticalException::class);
		ResponsesIndexLoader::importFile(567, PathsFS::FILENAME_EVENTS);
	}
}