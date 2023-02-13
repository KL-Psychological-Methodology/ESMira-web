<?php

namespace test\backend\admin\features\readPermission;

use backend\admin\features\readPermission\GetData;
use backend\admin\features\readPermission\GetRewardCodeData;
use backend\dataClasses\UserData;
use backend\subStores\ResponsesStore;
use backend\subStores\RewardCodeStore;
use backend\subStores\StudyStore;
use backend\subStores\UserDataStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseReadPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class GetRewardCodeDataTest extends BaseReadPermissionTestSetup {
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getRewardCodeStore',
			$this->createDataMock(RewardCodeStore::class, 'listRewardCodes', ['code1', 'code2']),
			$observer
		);
		
		$this->createStoreMock(
			'getStudyStore',
			$this->createDataMock(StudyStore::class, 'getStudyParticipants', ['user1', 'user2', 'user3']),
			$observer
		);
		
		$this->addDataMock($observer, 'getUserDataStore', function($userId) {
			return $this->createDataMock(UserDataStore::class, 'getUserData', function() use($userId) {
				$userData = new UserData(123, 234, 1);
				$userData->generatedRewardCode = $userId !== 'user2';
				return $userData;
			});
		});
		
		return $observer;
	}
	
	function test() {
		$qid = 'id';
		$this->setGet([
			'q_id' => $qid
		]);
		$obj = new GetRewardCodeData();
		
		$r = $obj->exec();
		$this->assertEquals([
			'rewardCodes' => ['code1', 'code2'],
			'participantsWithRewardCode' => ['user1', 'user3'],
			'participantsWithoutRewardCode' => ['user2']
		], $r);
		
		$this->assertDataMock('getStudyParticipants', [$this->studyId]);
		$this->assertDataMock('getUserDataStore', ['user1'], ['user2'], ['user3']);
		$this->assertDataMock('getUserData', [$this->studyId], [$this->studyId], [$this->studyId]);
	}
}