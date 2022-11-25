<?php
declare(strict_types=1);

namespace test\backend;

use backend\Configs;
use backend\DataStoreInterface;
use backend\Main;
use backend\subStores\ErrorReportStore;
use test\testConfigs\BaseTestSetup;

require_once __DIR__ .'/../../backend/autoload.php';

class MainTest extends BaseTestSetup {
	function test_report() {
		$errorReportStoreObserver = $this->createMock(ErrorReportStore::class);
		$errorReportStoreObserver->expects($this->once())
			->method('saveErrorReport')
			->with($this->equalTo('UnitTest'));
		
		$dataStoreObserver = $this->createMock(DataStoreInterface::class);
		$dataStoreObserver->expects($this->once())
			->method('getErrorReportStore')
			->willReturnCallback(function() use($errorReportStoreObserver) {
				return $errorReportStoreObserver;
			});
		
		Configs::injectDataStore($dataStoreObserver);
		
		Main::report('UnitTest');
		Configs::resetAll();
	}
	
	function test_getLang() {
		$this->assertEquals('en', Main::getLang());
		
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';
		$this->assertEquals('en', Main::getLang(false));
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = null;
		
		$_COOKIE['lang'] = 'en';
		$this->assertEquals('en', Main::getLang(false));
		$_COOKIE['lang'] = null;
		
		$_GET['lang'] = 'en';
		$this->assertEquals('en', Main::getLang(false));
	}
	
	function test_getAccessKey() {
		$_GET['key'] = 'key1';
		$this->assertEquals('key1', Main::getAccessKey());
		$_GET['key'] = null;
		
		$_COOKIE['access_key'] = 'key2';
		$this->assertEquals('key2', Main::getAccessKey());
		
		$_COOKIE['access_key'] = 'illegal$%&';
		$this->assertEquals('', Main::getAccessKey());
		$_COOKIE['access_key'] = null;
		
		$this->assertEquals('', Main::getAccessKey());
	}
	
	function test_strictCheckInput() {
		$this->assertTrue(Main::strictCheckInput(''));
		$this->assertTrue(Main::strictCheckInput('0123456789'));
		$this->assertTrue(Main::strictCheckInput('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'));
		$this->assertTrue(Main::strictCheckInput('äöüÄÖÜß'));
		$this->assertTrue(Main::strictCheckInput('Психічне здоров\'я у повсякденному житті'));
		$this->assertFalse(Main::strictCheckInput('!'));
		$this->assertFalse(Main::strictCheckInput(':'));
		$this->assertFalse(Main::strictCheckInput(':'));
	}
	
	function test_arrayToCSV() {
		$this->assertEquals('"key1","key2","key3"', Main::arrayToCSV(['key1', 'key2', 'key3'], ','));
	}
}