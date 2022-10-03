<?php

namespace test\backend\fileSystem\loader;

use backend\dataClasses\ErrorReportInfo;
use backend\fileSystem\loader\ErrorReportInfoLoader;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class ErrorReportInfoLoaderTest extends BaseDataFolderTestSetup {
	function test_import_and_export() {
		$source = [
			new ErrorReportInfo(1234567891,"note1", true),
			new ErrorReportInfo(1234567892,"note2", false),
			new ErrorReportInfo(1234567893,"note3", false)
		];
		
		ErrorReportInfoLoader::exportFile($source);
		
		$exported = ErrorReportInfoLoader::importFile();
		
		$this->assertEquals($source, $exported);
	}
}