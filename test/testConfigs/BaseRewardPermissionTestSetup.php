<?php

namespace testConfigs;

use PHPUnit\Framework\MockObject\Stub;

require_once __DIR__ . '/../autoload.php';

abstract class BaseRewardPermissionTestSetup extends BaseLoggedInPermissionTestSetup {
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
				return ['reward' => [$this->studyId]];
			});
		
		return $observer;
	}
}