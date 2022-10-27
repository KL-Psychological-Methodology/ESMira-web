<?php

namespace test\api;

use backend\exceptions\CriticalException;
use backend\exceptions\NoRewardCodeException;
use backend\JsonOutput;
use backend\Main;
use backend\subStores\StudyStore;
use backend\subStores\UserDataStore;
use PHPUnit\Framework\MockObject\Stub;
use stdClass;
use test\testConfigs\BaseApiTestSetup;

require_once __DIR__ .'/../../backend/autoload.php';

class RewardTest extends BaseApiTestSetup {
	private $code = 'code';
	private $studyId = 123;
	private $studyIdWithNoRewardCodeException = 234;
	private $studyIdWithCriticalExceptiom = 345;
	private $lockedStudyId = 456;
	
	protected function tearDown(): void {
		parent::tearDown();
		$this->isInit = true;
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$userDataStore = $this->createMock(UserDataStore::class);
		$userDataStore
			->method('generateRewardCode')
			->willReturnCallback(function(int $studyId) {
				switch($studyId) {
					case $this->studyIdWithCriticalExceptiom:
						throw new CriticalException('Test error');
					case $this->studyIdWithNoRewardCodeException:
						throw new NoRewardCodeException('Test error', 999);
					case $this->studyId:
						return $this->code;
					default:
						return 'error';
				}
			});
		$this->createStoreMock('getUserDataStore', $userDataStore, $observer);
		
		
		$studyStore = $this->createStub(StudyStore::class);
		$studyStore->method('isLocked')
			->willReturnCallback(function(int $studyId): bool {
				return $studyId == $this->lockedStudyId;
			});
		$this->createStoreMock('getStudyStore', $studyStore, $observer);
		
		return $observer;
	}
	
	function doTest(int $studyId) {
		Main::$defaultPostInput = json_encode([
			'serverVersion' => Main::ACCEPTED_SERVER_VERSION,
			'userId' => 'userId',
			'studyId' => $studyId
		]);
		require DIR_BASE .'/api/reward.php';
	}
	
	function test() {
		$this->doTest($this->studyId);
		$this->expectOutputString(JsonOutput::successObj(['errorCode' => 0, 'code' => $this->code]));
	}
	
	function test_with_no_reward_code() {
		$this->doTest($this->studyIdWithNoRewardCodeException);
		$this->expectOutputString(JsonOutput::successObj(['errorCode' => 999, 'errorMessage' => 'Test error', 'fulfilledQuestionnaires' => new stdClass()]));
	}
	
	function test_with_critical_error() {
		$this->doTest($this->studyIdWithCriticalExceptiom);
		$this->expectOutputString(JsonOutput::error('Test error'));
	}
	
	function test_with_locked_study() {
		$this->doTest($this->lockedStudyId);
		$this->expectOutputString(JsonOutput::error('This study is locked'));
	}
	
	function test_with_missing_data() {
		$this->assertMissingData(
			['studyId', 'userId'],
			function($a) {
				Main::$defaultPostInput = json_encode($a);
				require DIR_BASE .'/api/reward.php';
				$this->assertEquals(JsonOutput::error('Missing data'), ob_get_contents());
				ob_clean();
				return true;
			}
		);
	}
}