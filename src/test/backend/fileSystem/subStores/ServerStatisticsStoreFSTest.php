<?php

namespace test\backend\fileSystem\subStores;

use backend\Configs;
use backend\fileSystem\PathsFS;
use backend\fileSystem\subStores\StatisticsStoreWriterFS;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ . '/../../../../backend/autoload.php';

class ServerStatisticsStoreFSTest extends BaseDataFolderTestSetup {
	function tearDown(): void {
		$path = PathsFS::fileServerStatistics();
		if(file_exists($path))
			unlink($path);
	}
	
	function test_update() {
		$store = Configs::getDataStore()->getServerStatisticsStore();
		$defaultStatistics = $store->getStatisticsAsJsonString(); //file does not exist. Creates default obj
		$expectedStatisticsObj = json_decode($defaultStatistics);
		
		$this->assertEquals($defaultStatistics, $store->getStatisticsAsJsonString()); //file does not exist. Creates default obj
		
		$store->update(function(StatisticsStoreWriterFS $statistics) use ($expectedStatisticsObj) {
			$statistics->incrementUser();
			$expectedStatisticsObj->total->users += 1;
		});
		
		$this->assertEquals(json_encode($expectedStatisticsObj), file_get_contents(PathsFS::fileServerStatistics()));
		$this->assertNotEquals($defaultStatistics, $store->getStatisticsAsJsonString()); //will load file which has changed
		
		$store->update(function(StatisticsStoreWriterFS $statistics) use ($expectedStatisticsObj) {
			$statistics->incrementUser();
			return false;
		});
		
		$this->assertEquals(json_encode($expectedStatisticsObj), file_get_contents(PathsFS::fileServerStatistics()));
		$this->assertNotEquals($defaultStatistics, $store->getStatisticsAsJsonString()); //will load file which is still changed
	}
}