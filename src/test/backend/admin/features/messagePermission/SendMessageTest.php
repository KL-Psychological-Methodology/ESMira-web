<?php

namespace test\backend\admin\features\messagePermission;

use backend\admin\features\messagePermission\SendMessage;
use backend\dataClasses\UserData;
use backend\Main;
use backend\PageFlowException;
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
		$this->userData = [
			'recipient1' => new UserData(1, 111, 0, -1, $this->appType1, $this->appVersion1),
			'recipient2' => new UserData(2, 111, 0, -1, $this->appType1, '1.1'),
			'recipient3' => new UserData(3, 111, 0, -1, 'Type2', '1.1')
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
			[$this->studyId, $this->participants[0], Permission::getUser(), $this->msgContent],
			[$this->studyId, $this->participants[1], Permission::getUser(), $this->msgContent],
			[$this->studyId, $this->participants[2], Permission::getUser(), $this->msgContent]
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
			[$this->studyId, $this->participants[0], Permission::getUser(), $this->msgContent],
			[$this->studyId, $this->participants[1], Permission::getUser(), $this->msgContent]
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
			[$this->studyId, $this->participants[0], Permission::getUser(), $this->msgContent]
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
		$this->assertDataMock('sendMessage', [$this->studyId, $this->userId, Permission::getUser(), $this->msgContent]);
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