<?php

namespace test\testConfigs;

use backend\admin\NoPermission;
use test\testConfigs\BaseMockedTestSetup;
use backend\PageFlowException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\Stub;

require_once __DIR__ . '/../../backend/autoload.php';

abstract class BaseNoPermissionTestSetup extends BaseMockedTestSetup {
	protected $isInit = true;
	
	public function setUp(): void {
		parent::setUp();
		$this->isInit = true;
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		$observer
			->method('isInit')
			->willReturnCallback(function() {
				return $this->isInit;
			});
		
		return $observer;
	}
	
	
	protected function assertDataMockFromPost(NoPermission $obj, string $method, array $postArray, array $expectedCalls = null): array {
		$this->setPost($postArray);
		$r = $obj->exec();
		$this->assertDataMock($method, $expectedCalls ?? array_values($postArray));
		return $r;
	}
	protected function assertMissingDataForFeatureObj(string $class, array $array, bool $useGetInsteadOfPost = false, bool $useExecAndOutput = false) {
		$this->assertMissingData(
			$array,
			function(array $a) use($class, $useExecAndOutput, $useGetInsteadOfPost) {
				try {
					if($useGetInsteadOfPost)
						$this->setGet($a);
					else
						$this->setPost($a);
					
					$obj = new $class;
					if($useExecAndOutput)
						$obj->execAndOutput();
					else
						$obj->exec();
					throw new ExpectationFailedException("Nothing was thrown.\n" .print_r($a, true));
				}
				catch(PageFlowException $e) {
					$errorMsg = $e->getMessage();
					if($errorMsg == 'Missing data' || $errorMsg == 'Missing study id')
						return true;
					else
						throw new ExpectationFailedException("The wrong error was thrown: " .$e->getMessage());
				}
			});
	}
}