<?php

namespace test\backend\fileSystem\subStores;

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\dataClasses\ErrorReportInfo;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class ErrorReportStoreFSTest extends BaseDataFolderTestSetup {
	function test_check_add_and_remove_errorReport() {
		$errorStore = Configs::getDataStore()->getErrorReportStore();
		
		$this->assertFalse($errorStore->hasErrorReports());
		$this->assertEquals([], $errorStore->getList());
		
		$errorStore->saveErrorReport('test1');
		$errorStore->saveErrorReport('test2');
		$errorStore->saveErrorReport('test3');
		$this->assertTrue($errorStore->hasErrorReports());
		$list = $errorStore->getList();
		$this->assertCount(3, $list);
		
		$timestamp1 = $list[0]->timestamp;
		$timestamp2 = $list[1]->timestamp;
		$timestamp3 = $list[2]->timestamp;
		
		$this->assertEquals('test1', $errorStore->getErrorReport($timestamp1));
		$this->assertEquals('test2', $errorStore->getErrorReport($timestamp2));
		$this->assertEquals('test3', $errorStore->getErrorReport($timestamp3));
		
		$errorStore->removeErrorReport($timestamp2);
		
		$list = $errorStore->getList();
		$this->assertCount(2, $list);
		$this->assertEquals($timestamp1, $list[0]->timestamp);
		$this->assertEquals($timestamp3, $list[1]->timestamp);
	}
	
	function test_changeErrorReport() {
		$errorStore = Configs::getDataStore()->getErrorReportStore();
		
		$errorStore->saveErrorReport('test1');
		$list = $errorStore->getList();
		$this->assertFalse($list[0]->seen);
		$this->assertEquals('', $list[0]->note);
		
		$timestamp = $list[0]->timestamp;
		
		$errorStore->changeErrorReport(new ErrorReportInfo($timestamp, 'note', false));
		$list = $errorStore->getList();
		$this->assertFalse($list[0]->seen);
		$this->assertEquals('note', $list[0]->note);
		
		$errorStore->changeErrorReport(new ErrorReportInfo($timestamp, '', true));
		$list = $errorStore->getList();
		$this->assertTrue($list[0]->seen);
		$this->assertEquals('', $list[0]->note);
	}
	
	function test_get_not_existing_errorReport() {
		$errorStore = Configs::getDataStore()->getErrorReportStore();
		$this->expectException(CriticalException::class);
		$errorStore->getErrorReport(123);
	}
	function test_change_not_existing_errorReport() {
		$errorStore = Configs::getDataStore()->getErrorReportStore();
		$this->expectException(CriticalException::class);
		$errorStore->changeErrorReport(new ErrorReportInfo(123, '234', true));
	}
	function test_remove_not_existing_errorReport() {
		$errorStore = Configs::getDataStore()->getErrorReportStore();
		$this->expectException(CriticalException::class);
		$errorStore->removeErrorReport(123);
	}
}