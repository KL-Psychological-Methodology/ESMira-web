<?php

namespace test\backend\fileSystem\loader;

require_once __DIR__ .'/../../../../backend/autoload.php';

use backend\dataClasses\UserData;
use backend\fileSystem\loader\UserDataLoader;
use test\testConfigs\BaseTestSetup;

class UserDataLoaderTest extends BaseTestSetup {
	function test_import_and_export() {
		$source = new UserData(5, 123, 10, 1, 'UnitTest', '1.1');
		$exported = UserDataLoader::import(UserDataLoader::export($source));
		
		$this->assertEquals($source->userIdInteger, $exported->userIdInteger);
		$this->assertEquals($source->token, $exported->token);
		$this->assertEquals($source->dataSetCount, $exported->dataSetCount);
		$this->assertEquals($source->group, $exported->group);
		$this->assertEquals($source->appType, $exported->appType);
		$this->assertEquals($source->appVersion, $exported->appVersion);
	}
}