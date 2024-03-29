<?php

namespace test\backend;

use backend\Configs;
use backend\fileSystem\DataStoreFS;
use backend\Paths;
use test\testConfigs\BaseTestSetup;

require_once __DIR__ .'/../../backend/autoload.php';

class ConfigsTest extends BaseTestSetup {
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		Configs::resetAll();
	}
	
	function test_getDataStore() {
		$dataStore = Configs::getDataStore();
		$this->assertInstanceOf(DataStoreFS::class, $dataStore);
	}
	function test_get() {
		$this->assertEquals(null, Configs::get('test'));
		$this->assertEquals('backend\fileSystem\DataStoreFS', Configs::get('dataStore'));
	}
	function test_getAll() {
		$array = require Paths::FILE_DEFAULT_CONFIG;
		$this->assertEquals($array, Configs::getAll());
		$this->assertEquals($array, Configs::getAll()); //use cache
		
	}
	function test_getDefaultAll() {
		$array = require Paths::FILE_DEFAULT_CONFIG;
		$this->assertEquals($array, Configs::getDefaultAll());
	}
	
	function test_get_serverName() {
		Configs::injectConfig('configs.serverName.injected.php');
		if(isset($_GET['lang'])) //in case it was already defined in another test
			unset($_GET['lang']);
		$this->assertEquals('test name - de', Configs::getServerName());
		
		$_GET['lang'] = 'en';
		$this->assertEquals('test name - en', Configs::getServerName());
		Configs::resetAll();
	}
}