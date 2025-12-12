<?php

namespace testConfigs;

use backend\JsonOutput;
use PHPUnit\Framework\MockObject\Stub;

require_once __DIR__ . '/../autoload.php';

abstract class BaseApiTestSetup extends BaseTestWithDataStoreSetup {
	protected function assertIsInit($filename) {
		$this->isInit = false;
		
		$this->expectOutputString(JsonOutput::error('ESMira is not initialized yet.'));
		require DIR_BASE ."/api/$filename.php";
	}
	
	protected function assertIsReady($filename) {
		$this->isReady = false;
		
		$this->expectOutputString(JsonOutput::error('Server is not ready.'));
		require DIR_BASE ."/api/$filename.php";
	}
	
	protected function assertMissingDataForApi(array $array, string $filename, bool $useGetInsteadOfPost = false) {
		$this->assertMissingData(
			$array,
			function($a) use($filename, $useGetInsteadOfPost) {
				if($useGetInsteadOfPost)
					$this->setGet($a);
				else
					$this->setPost($a);
				
				require DIR_BASE ."/api/$filename.php";
				$this->assertEquals(JsonOutput::error('Missing data'), ob_get_contents());
				ob_clean();
				return true;
			}
			);
	}
}