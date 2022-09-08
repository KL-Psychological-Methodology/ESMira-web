<?php

namespace test\api;

use backend\JsonOutput;
use backend\subStores\ServerStatisticsStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseApiTestSetup;

require_once __DIR__ .'/../../backend/autoload.php';

class ServerStatisticsTest extends BaseApiTestSetup {
	private $content = '{"content"}';
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$statisticsStore = $this->createStub(ServerStatisticsStore::class);
		$statisticsStore->method('getStatisticsAsJsonString')
			->willReturn($this->content);
		$this->createStoreMock('getServerStatisticsStore', $statisticsStore, $observer);
		
		return $observer;
	}
	
	function test() {
		require DIR_BASE .'/api/server_statistics.php';
		$this->expectOutputString(JsonOutput::successString($this->content));
	}
	
	function test_without_init() {
		$this->assertIsInit('server_statistics');
	}
}