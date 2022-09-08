<?php
declare(strict_types=1);

namespace test\backend\fileSystem;

use backend\FileSystemBasics;
use test\testConfigs\BaseTestSetup;

require_once __DIR__ .'/../../../backend/autoload.php';
require_once __DIR__ .'/../../testConfigs/variables.php';

class FileSystemBasicsTest extends BaseTestSetup {
	function test_createFolder_writeFile_and_emptyFolder() {
		$testFolder = TEST_DATA_FOLDER;
		$testSubFolder = TEST_DATA_FOLDER .'sub/';
		$testFile = $testFolder .'testfile.txt';
		$testContent = 'test123';
		
		FileSystemBasics::createFolder($testFolder);
		FileSystemBasics::createFolder($testSubFolder);
		$this->assertTrue(file_exists($testFolder));
		
		FileSystemBasics::writeFile($testFile, $testContent);
		$this->assertTrue(file_exists($testFile));
		
		FileSystemBasics::emptyFolder($testFolder);
		$this->assertFalse(file_exists($testFile));
		$this->assertFalse(file_exists($testSubFolder));
		
		rmdir($testFolder);
	}
}