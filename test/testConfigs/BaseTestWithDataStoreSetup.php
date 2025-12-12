<?php

namespace testConfigs;

use backend\JsonOutput;
use PHPUnit\Framework\MockObject\Stub;

require_once __DIR__ . '/../autoload.php';

abstract class BaseTestWithDataStoreSetup extends BaseMockedTestSetup {
	protected bool $isInit = true;
	protected bool $isReady = true;
	
	public function setUp(): void {
		parent::setUp();
		$this->isInit = true;
		$this->isReady = true;
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		$observer
			->method('isInit')
			->willReturnCallback(function() {
				return $this->isInit;
			});
		
		$observer
			->method('isReady')
			->willReturnCallback(function() {
				return $this->isReady;
			});
		return $observer;
	}
}