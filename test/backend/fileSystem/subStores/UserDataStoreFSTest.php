<?php
declare(strict_types=1);

namespace backend\fileSystem\subStores;

require_once __DIR__ . '/../../../autoload.php';

use backend\fileSystem\subStores\UserDataStoreFS;
use backend\ResponsesIndex;
use stdClass;
use testConfigs\BaseDataFolderTestSetup;

class UserDataStoreFSTest extends BaseDataFolderTestSetup {
	private $studyId = 123;
	private $group = 5;
	
	function setUp(): void {
		parent::setUp();
		self::setUpBeforeClass();
		$this->createEmptyStudy($this->studyId);
	}
	function tearDown(): void {
		parent::tearDown();
		self::tearDownAfterClass();
	}
	
	private function createDataSet(): stdClass {
		return (object) [
			'group' => $this->group
		];
	}
	
	function test_writeAndClose() {
		$userId = 'test1';
		$appType = 'Test';
		$appVersion = '1';
		$studyLang = 'en';
		
		$normalDataSet = $this->createDataSet();
		$questionnaireDataSet = $this->createDataSet();
		$questionnaireDataSet->eventType = 'questionnaire';
		$questionnaireDataSet->questionnaireInternalId = 1234;
		
		$userDataStore = new UserDataStoreFS($userId);
		$userDataStore->addDataSetForSaving($this->studyId, $normalDataSet, $appType, $appVersion, $studyLang);
		$userDataStore->addDataSetForSaving($this->studyId, $normalDataSet, $appType, $appVersion, $studyLang);
		$userDataStore->addDataSetForSaving($this->studyId, $questionnaireDataSet, $appType, $appVersion, $studyLang);
		$userDataStore->addDataSetForSaving($this->studyId, $questionnaireDataSet, $appType, $appVersion, $studyLang);
		$questionnaireDataSet->questionnaireInternalId = 2345;
		$userDataStore->addDataSetForSaving($this->studyId, $questionnaireDataSet, $appType, $appVersion, $studyLang);
		$userDataStore->writeAndClose();
		
		//make sure token file is reloaded:
		$userDataStore = new UserDataStoreFS($userId);
		$userDataStore->addDataSetForSaving($this->studyId, $normalDataSet, $appType, $appVersion, $studyLang);
		$userDataStore->writeAndClose();
		
		$userData = $userDataStore->getUserData($this->studyId);
		$this->assertEquals($this->group, $userData->group);
		$this->assertEquals(6, $userData->dataSetCount);
		$this->assertEquals([1234 => 2, 2345 => 1], $userData->questionnaireDataSetCount);
		$this->assertEquals($appType, $userData->appType);
		$this->assertEquals($appVersion, $userData->appVersion);
	}
	
	
	public function test_isOutdated() {
		$userId = 'test1';

		$userTokenSaver = new UserDataStoreFS($userId);

		//sets the expected token to -1:
		$userTokenSaver->addDataSetForSaving($this->studyId, $this->createDataSet(), 'UnitTest', '1', 'en');

		$this->assertFalse($userTokenSaver->isOutdated($this->studyId, 555, false));//is not a reupload
		$this->assertTrue($userTokenSaver->isOutdated($this->studyId, 555, true));
		$this->assertFalse($userTokenSaver->isOutdated($this->studyId, -1, true));
	}
	
	public function test_getNewStudyTokens() {
		$userDataStore = new UserDataStoreFS('test1');
		
		$this->createEmptyStudy(147);
		$this->createEmptyStudy(258);
		$this->createEmptyStudy(369);
		
		$this->assertEquals([], $userDataStore->getNewStudyTokens());
		
		$userDataStore->addDataSetForSaving(147, $this->createDataSet(), 'UnitTest', '1', 'en');
		$userDataStore->addDataSetForSaving(258, $this->createDataSet(), 'UnitTest', '1', 'en');
		$userDataStore->addDataSetForSaving(369, $this->createDataSet(), 'UnitTest', '1', 'en');
		
		$tokens = $userDataStore->getNewStudyTokens();
		$this->assertArrayHasKey(147, $tokens);
		$this->assertArrayHasKey(258, $tokens);
		$this->assertArrayHasKey(369, $tokens);
	}
	
	public function test_generateRewardCode() {
		$studyId = $this->studyId;
		$this->createStudy((object) [
			'id' => $studyId,
			'questionnaires' => [
				(object) [
					'internalId' => 1234,
					'minDataSetsForReward' => 2
				]
			],
			'enableRewardSystem' => true,
			'rewardVisibleAfterDays' => 0
		], [1234 => new ResponsesIndex()]);
		
		$userDataStoreForDataSets = new UserDataStoreFS('test1');
		$questionnaireDataSet = $this->createDataSet();
		$questionnaireDataSet->questionnaireInternalId = 1234;
		$questionnaireDataSet->eventType = 'questionnaire';
		$userDataStoreForDataSets->addDataSetForSaving($studyId, $questionnaireDataSet, 'UnitTest', '1', 'en');
		$userDataStoreForDataSets->addDataSetForSaving($studyId, $questionnaireDataSet, 'UnitTest', '1', 'en');
		$userDataStoreForDataSets->writeAndClose();
		
		
		$userDataStoreForRewardCode = new UserDataStoreFS('test1');
		$code = $userDataStoreForRewardCode->generateRewardCode($studyId);
		
		$this->assertGreaterThanOrEqual(5, strlen($code));
		
		$this->expectErrorMessage('Reward code was already generated');
		
		$userDataStoreForRewardCode = new UserDataStoreFS('test1');
		$userDataStoreForRewardCode->generateRewardCode($studyId);
	}
	public function test_generateRewardCode_with_disabled_reward_system() {
		$studyId = $this->studyId;
		$this->createStudy((object) [
			'id' => $studyId,
			'enableRewardSystem' => false
		]);
		
		$userDataStoreForRewardCode = new UserDataStoreFS('test1');
		$this->expectErrorMessage('Reward codes are disabled');
		$userDataStoreForRewardCode->generateRewardCode($studyId);
	}
	public function test_generateRewardCode_with_too_few_datasets() {
		$studyId = $this->studyId;
		$this->createStudy((object) [
			'id' => $studyId,
			'questionnaires' => [
				(object) [
					'internalId' => 1234,
					'minDataSetsForReward' => 3
				]
			],
			'enableRewardSystem' => true,
			'rewardVisibleAfterDays' => 0
		], [1234 => new ResponsesIndex()]);
		
		$userDataStoreForDataSets = new UserDataStoreFS('test1');
		$questionnaireDataSet = $this->createDataSet();
		$questionnaireDataSet->questionnaireInternalId = 1234;
		$questionnaireDataSet->eventType = 'questionnaire';
		$userDataStoreForDataSets->addDataSetForSaving($studyId, $questionnaireDataSet, 'UnitTest', '1', 'en');
		$userDataStoreForDataSets->addDataSetForSaving($studyId, $questionnaireDataSet, 'UnitTest', '1', 'en');
		$userDataStoreForDataSets->writeAndClose();
		
		$userDataStoreForRewardCode = new UserDataStoreFS('test1');
		$this->expectErrorMessage('Not all conditions are fulfilled');
		$userDataStoreForRewardCode->generateRewardCode($studyId);
	}
	public function test_generateRewardCode_with_rewards_not_visible_yet() {
		$studyId = $this->studyId;
		$this->createStudy((object) [
			'id' => $studyId,
			'enableRewardSystem' => true,
			'rewardVisibleAfterDays' => 1
		]);
		
		$userDataStoreForRewardCode = new UserDataStoreFS('test1');
		$this->expectErrorMessage('Rewards are not accessible yet');
		$userDataStoreForRewardCode->generateRewardCode($studyId);
	}
}