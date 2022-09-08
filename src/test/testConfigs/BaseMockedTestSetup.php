<?php

namespace test\testConfigs;

use backend\Configs;
use backend\DataStoreInterface;
use test\testConfigs\SkipArgument;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use test\testConfigs\BaseTestSetup;

require_once __DIR__ . '/../../backend/autoload.php';

class BaseMockedTestSetup extends BaseTestSetup {
	private $argumentData = [];
	
	/**
	 * @var DataStoreInterface
	 */
	protected $dataStoreObserver;
	
	function setUp(): void {
		$this->dataStoreObserver = $this->setUpDataStoreObserver();
		Configs::injectDataStore($this->dataStoreObserver);
	}
	protected function tearDown(): void {
		parent::tearDown();
		$this->setPost();
		$this->setGet();
	}
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		Configs::resetAll();
	}
	
	
	protected function setUpDataStoreObserver(): Stub {
		return $this->createMock(DataStoreInterface::class);
	}
	
	
	protected function createStoreMock(string $method, Stub $observer, Stub $parentObserver) {
		$parentObserver
			->method($method)
			->willReturnCallback(function() use($observer) {
				return $observer;
			});
	}
	
	protected function createDataMock(string $class, string $method, /*mixed*/ $return = null): Stub {
		$store = $this->createMock($class);
		return $this->addDataMock($store, $method, $return);
	}
	protected function addDataMock(Stub $store, string $method, /*mixed*/ $return = null): Stub {
		$store->method($method)
			->willReturnCallback(function(... $arguments) use($method, $return) {
				if(!isset($this->argumentData[$method]))
					$this->argumentData[$method] = [];
				$this->argumentData[$method][] = $arguments;
				return is_callable($return) ? call_user_func_array($return, $arguments) : $return;
//				return is_callable($return) ? $return() : $return;
			});
		return $store;
	}
	
	
	protected function setPost(array $data = []) {
		$_POST = $data;
	}
	protected function setGet(array $data = []) {
		$_GET = $data;
	}
	
	
	protected function assertMissingData(array $array, callable $exec) {
		$caughtNum = 0;
		$expectedNum = count($array);
		for($i=$expectedNum-1; $i >=0; --$i) {
			$a = $array;
			array_splice($a, $i, 1);
			
			if($exec($a))
				++$caughtNum;
		}
		$this->assertEquals($expectedNum, $caughtNum, "Expected $expectedNum exceptions but got $caughtNum");
	}
	
	protected function assertDataMock(string $method, ...$expectedCalls) {
		if(!isset($this->argumentData[$method]))
			throw new ExpectationFailedException("No calls were saved for \"$method\"");
		
		$savedCalls = $this->argumentData[$method];
		$expectedCallCount = count($expectedCalls);
		$actualCallCount = count($savedCalls);
		$this->assertEquals($expectedCallCount, $actualCallCount, "$method() was expected to be called $expectedCallCount times. but was called $actualCallCount times");
		foreach($savedCalls as $i => $actualArguments) {
			$expectedArguments = $expectedCalls[$i];
			
			$expectedArgumentsCount = count($expectedArguments);
			$actualArgumentsCount = count($actualArguments);
			$this->assertEquals($expectedArgumentsCount, $actualArgumentsCount, "Call $i of $method() was expected to have $expectedArgumentsCount arguments. but had $actualArgumentsCount arguments");
			foreach($actualArguments as $i2 => $actualArgument) {
				$expectedArgument = $expectedArguments[$i2];
				if($expectedArgument instanceof SkipArgument)
					continue;
				$this->assertEquals($expectedArgument, $actualArgument, "$i. $method(): Argument $i2 is unexpected");
			}
		}
		$this->argumentData[$method] = [];
	}
}