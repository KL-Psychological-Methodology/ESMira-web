<?php

namespace testConfigs;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../autoload.php';

abstract class BaseMessagePermissionTestSetup extends BaseLoggedInPermissionTestSetup {
	protected $studyId = 123;
	public function setUp(): void {
		parent::setUp();
		$this->setPost(['study_id' => $this->studyId]);
	}
	
	protected function setUpAccountStoreObserver(): Stub {
		$observer =  parent::setUpAccountStoreObserver();
		
		$observer
			->method('getPermissions')
			->willReturnCallback(function() {
				return ['msg' => [$this->studyId]];
			});
		
		return $observer;
	}
}