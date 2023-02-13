<?php
declare(strict_types=1);

namespace test\backend\fileSystem\subStores;

require_once __DIR__ . '/../../../../backend/autoload.php';

use backend\Configs;
use backend\dataClasses\RewardCodeData;
use test\testConfigs\BaseDataFolderTestSetup;

class RewardCodeStoreFSTest extends BaseDataFolderTestSetup {
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
	
	function test_setAndGetRewardCode() {
		$rewardStore = Configs::getDataStore()->getRewardCodeStore();
		
		$questionnaireData = [123 => 5, 234 => 7];
		$rewardCodeData = new RewardCodeData($questionnaireData);
		$rewardCodeData->code = 'code1';
		$rewardStore->saveRewardCodeData($this->studyId, $rewardCodeData);
		
		$this->assertTrue($rewardStore->hasRewardCode($this->studyId, $rewardCodeData->code));
		$this->assertFalse($rewardStore->hasRewardCode($this->studyId, 'doesNotExist'));
		
		$loadedData = $rewardStore->getRewardCodeData($this->studyId, $rewardCodeData->code);
		$this->assertEquals($rewardCodeData->timestamp, $loadedData->timestamp);
		$this->assertEquals($questionnaireData, $loadedData->questionnaireDataSetCount);
	}
	
	function test_listRewardCodes() {
		$rewardStore = Configs::getDataStore()->getRewardCodeStore();
		
		$rewardCodeData = new RewardCodeData([]);
		
		$rewardCodeData->code = 'code1';
		$rewardStore->saveRewardCodeData($this->studyId, $rewardCodeData);
		
		$rewardCodeData->code = 'code2';
		$rewardStore->saveRewardCodeData($this->studyId, $rewardCodeData);
		
		$rewardCodeData->code = 'code3';
		$rewardStore->saveRewardCodeData($this->studyId, $rewardCodeData);
		
		$rewardCodeData->code = 'code4';
		$rewardStore->saveRewardCodeData($this->studyId, $rewardCodeData);
		
		$list = $rewardStore->listRewardCodes($this->studyId);
		
		$this->assertCount(4, $list);
		$this->assertContains('code1', $list);
	}
	
	
}