<?php

namespace test\testConfigs;

use Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Throwable;

require_once __DIR__ . '/../../backend/autoload.php';

abstract class BaseTestSetup extends TestCase {
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		$_COOKIE = [];
		$_GET = [];
		$_POST = [];
		$_SERVER = [];
		$_SESSION = [];
	}
	
	protected function setPost(array $data = []) {
		$_POST = $data;
	}
	protected function setGet(array $data = []) {
		$_GET = $data;
	}
	
	/**
	 * @throws ExpectationFailedException
	 */
	protected function assertException(callable $callback, string $type, ?callable $assertMessage = null): void {
		try {
			$callback();
		}
		catch (Throwable $e) {
			self::assertInstanceOf($type, $e);
			if($assertMessage) {
				$assertMessage($e->getMessage());
			}
			return;
		}
		throw new ExpectationFailedException('Nothing was thrown!');
	}
}