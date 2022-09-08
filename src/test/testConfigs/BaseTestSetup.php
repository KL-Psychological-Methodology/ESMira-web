<?php

namespace test\testConfigs;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

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
}