<?php

namespace test\backend\fileSystem\subStores;

use backend\Configs;
use backend\fileSystem\subStores\ServerServerStatisticsStoreFS;
use backend\fileSystem\subStores\StatisticsStoreWriterFS;
use test\testConfigs\BaseTestSetup;

require_once __DIR__ . '/../../../../backend/autoload.php';

class StatisticsStoreWriterFSTest extends BaseTestSetup {
	/**
	 * @var StatisticsStoreWriterFS
	 */
	private $writer;
	function setUp(): void {
		$statistics = (new ServerServerStatisticsStoreFS())->getStatisticsAsJsonString();
		$this->writer = new StatisticsStoreWriterFS(json_decode($statistics));
	}
	
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		Configs::injectConfig('configs.dataFolder.injected.php');
	}
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		Configs::resetAll();
	}
	
	function test_increments() {
		$this->writer->incrementStudies();
		$this->assertEquals(1, $this->writer->getStatisticsObj()->total->studies);
		
		$this->writer->decrementStudies();
		$this->assertEquals(0, $this->writer->getStatisticsObj()->total->studies);
		
		$this->writer->incrementUser();
		$this->assertEquals(1, $this->writer->getStatisticsObj()->total->users);
		
		$this->writer->incrementAndroid();
		$this->assertEquals(1, $this->writer->getStatisticsObj()->total->android);
		
		$this->writer->incrementIos();
		$this->assertEquals(1, $this->writer->getStatisticsObj()->total->ios);
		
		$this->writer->incrementWeb();
		$this->assertEquals(1, $this->writer->getStatisticsObj()->total->web);
	}
	
	function test_addDataToDay() {
		$questionnaireAdd = 2;
		$joinedAdd = 6;
		$startOfDay1 = 5;
		$startOfDay2 = 10;
		$afterFirstEntry = 7;
		$addCount = 4;
		$appType = 'UnitTest';
		$appVersion1 = '0.0.0';
		$appVersion2 = '9.9.9';
		$versionString1 = "$appType $appVersion1";
		$versionString2 = "$appType $appVersion2";
		$week = date('w');
		
		
		$this->writer->addDataToDay(0, $startOfDay1, $appType, $appVersion1, $questionnaireAdd, $joinedAdd); //will be removed from days
		$this->assertObjectHasAttribute($startOfDay1, $this->writer->getStatisticsObj()->days);
		$this->assertObjectHasAttribute($versionString1, $this->writer->getStatisticsObj()->days->{$startOfDay1}->appVersion);
		
		$this->writer->addDataToDay($afterFirstEntry, $startOfDay2, $appType, $appVersion1, $questionnaireAdd, $joinedAdd);
		$this->assertObjectNotHasAttribute($startOfDay1, $this->writer->getStatisticsObj()->days);
		$this->assertObjectHasAttribute($versionString1, $this->writer->getStatisticsObj()->days->{$startOfDay2}->appVersion);
		
		
		$this->writer->addDataToDay($afterFirstEntry, $startOfDay2, $appType, $appVersion2, $questionnaireAdd, $joinedAdd);
		$this->assertObjectHasAttribute($versionString2, $this->writer->getStatisticsObj()->days->{$startOfDay2}->appVersion);
		
		
		$this->writer->addDataToDay($afterFirstEntry, $startOfDay2, $appType, $appVersion2, $questionnaireAdd, $joinedAdd);
		$this->assertObjectHasAttribute($versionString2, $this->writer->getStatisticsObj()->days->{$startOfDay2}->appVersion);
		
		
		$this->assertEquals($questionnaireAdd * $addCount, $this->writer->getStatisticsObj()->total->questionnaire);
		$this->assertEquals($joinedAdd * $addCount, $this->writer->getStatisticsObj()->total->joined);
		$this->assertEquals($questionnaireAdd * $addCount, $this->writer->getStatisticsObj()->week->questionnaire[$week]);
		$this->assertEquals($joinedAdd * $addCount, $this->writer->getStatisticsObj()->week->joined[$week]);
	}
}