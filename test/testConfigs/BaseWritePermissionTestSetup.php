<?php

namespace testConfigs;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../autoload.php';

abstract class BaseWritePermissionTestSetup extends BaseLoggedInPermissionTestSetup {
	protected $writePermissions = [];
	protected $publishPermissions = [];
	
	public function setUp(): void {
		parent::setUp();
		$this->setPost(['study_id' => $this->studyId]);
		$this->writePermissions[] = $this->studyId;
	}
	
	protected function setUpAccountStoreObserver(): Stub {
		$observer =  parent::setUpAccountStoreObserver();
		
		$observer
			->method('getPermissions')
			->willReturnCallback(function() {
				return ['write' => $this->writePermissions, 'publish' => $this->publishPermissions];
			});
		
		return $observer;
	}
}