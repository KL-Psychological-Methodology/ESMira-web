<?php

namespace test\backend\admin\features\messagePermission;

use backend\admin\features\messagePermission\SendMessage;
use backend\dataClasses\UserData;
use backend\Main;
use backend\exceptions\PageFlowException;
use backend\Permission;
use backend\subStores\MessagesStore;
use backend\subStores\StudyStore;
use backend\subStores\UserDataStore;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseMessagePermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class SendMessageTest extends BaseMessagePermissionTestSetup {
	private $userId = 'user1';
	private $msgContent = 'content';
	private $appVersion1 = '1.0';
	private $appType1 = 'Type1';
	private $participants = ['recipient1', 'recipient2', 'recipient3'];
	/**
	 * @var UserData[]
	 */
	private $userData;
	
	public function setUp(): void {
		parent::setUp();
		$userData1 = new UserData(1, 111, 0);
		$userData1->appType = $this->appType1;
		$userData1->appVersion = $this->appVersion1;
		$userData2 = new UserData(1, 111, 0);
		$userData2->appType = $this->appType1;
		$userData2->appVersion = '1.1';
		$userData3 = new UserData(1, 111, 0);
		$userData3->appType = 'Type2';
		$userData3->appVersion = '1.1';
		
		$this->userData = [
			'recipient1' => $userData1,
			'recipient2' => $userData2,
			'recipient3' => $userData3
		];
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getMessagesStore',
			$this->createDataMock(MessagesStore::class, 'sendMessage', 123456789),
			$observer
		);
		
		$this->createStoreMock(
			'getStudyStore',
			$this->createDataMock(StudyStore::class, 'getStudyParticipants', $this->participants),
			$observer
		);
		
		
		$observer->expects($this->any())
			->method('getUserDataStore')
			->willReturnCallback(function($userId) use($observer) {
				if(!isset($this->userData[$userId]))
					throw new ExpectationFailedException('Unknown userId');
				return $this->createDataMock(UserDataStore::class, 'getUserData', $this->userData[$userId]);
			});
		
		return $observer;
	}
	
	function test_toAll() {
		Main::$defaultPostInput = json_encode([
			'content' => $this->msgContent,
			'toAll' => true
		]);
		$obj = new SendMessage();
		$obj->exec();
		$this->assertDataMock('sendMessage',
			[$this->studyId, $this->participants[0], Permission::getAccountName(), $this->msgContent],
			[$this->studyId, $this->participants[1], Permission::getAccountName(), $this->msgContent],
			[$this->studyId, $this->participants[2], Permission::getAccountName(), $this->msgContent]
		);
	}
	
	function test_toAll_with_appType() {
		Main::$defaultPostInput = json_encode([
			'content' => $this->msgContent,
			'appType' => $this->appType1,
			'toAll' => true
		]);
		$obj = new SendMessage();
		$obj->exec();
		$this->assertDataMock('sendMessage',
			[$this->studyId, $this->participants[0], Permission::getAccountName(), $this->msgContent],
			[$this->studyId, $this->participants[1], Permission::getAccountName(), $this->msgContent]
		);
	}
	
	function test_toAll_with_appVersion() {
		Main::$defaultPostInput = json_encode([
			'content' => $this->msgContent,
			'appVersion' => $this->appVersion1,
			'toAll' => true
		]);
		$obj = new SendMessage();
		$obj->exec();
		$this->assertDataMock('sendMessage',
			[$this->studyId, $this->participants[0], Permission::getAccountName(), $this->msgContent]
		);
	}
	
	function test_single() {
		Main::$defaultPostInput = json_encode([
			'user' => $this->userId,
			'timestamps' => [123],
			'content' => $this->msgContent,
			'toAll' => false
		]);
		$obj = new SendMessage();
		$obj->exec();
		$this->assertDataMock('sendMessage', [$this->studyId, $this->userId, Permission::getAccountName(), $this->msgContent]);
	}
	
	function test_with_short_content() {
		Main::$defaultPostInput = json_encode([
			'user' => $this->userId,
			'content' => '1',
			'toAll' => false
		]);
		$obj = new SendMessage();
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
	function test_with_faulty_recipient() {
		Main::$defaultPostInput = json_encode([
			'user' => '$qweqwe',
			'content' => $this->msgContent,
			'toAll' => false
		]);
		$obj = new SendMessage();
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
	function test_with_missing_data() {
		Main::$defaultPostInput = json_encode([
			'toAll' => false
		]);
		$obj = new SendMessage();
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
	function test_with_faulty_data() {
		Main::$defaultPostInput = '';
		$obj = new SendMessage();
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
}