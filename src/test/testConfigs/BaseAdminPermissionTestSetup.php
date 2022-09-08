<?php

namespace test\testConfigs;

use test\testConfigs\BaseLoggedInPermissionTestSetup;
use PHPUnit\Framework\MockObject\Stub;

require_once __DIR__ . '/../../backend/autoload.php';

abstract class BaseAdminPermissionTestSetup extends BaseLoggedInPermissionTestSetup {
	protected $isAdmin = true;
	public function setUp(): void {
		parent::setUp();
		$this->isAdmin = true;
	}
	
	protected function setUpUserStoreObserver(): Stub {
		$observer = parent::setUpUserStoreObserver();
		
		$observer
			->method('getPermissions')
			->willReturnCallback(function() {
				return ['admin' => $this->isAdmin];
			});
		
		return $observer;
	}
}