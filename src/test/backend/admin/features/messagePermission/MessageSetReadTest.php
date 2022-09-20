<?php

namespace test\backend\admin\features\messagePermission;

use backend\admin\features\messagePermission\MessageSetRead;
use backend\Main;
use backend\exceptions\PageFlowException;
use backend\subStores\MessagesStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseMessagePermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class MessageSetReadTest extends BaseMessagePermissionTestSetup {
	private $accountName = 'user1';
	private $timestamps = [123, 234, 345];
	private $messagesListContent = ['entry1', 'entry2'];
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getMessagesStore',
			$this->createDataMock(MessagesStore::class, 'setMessagesAsRead', $this->messagesListContent),
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		Main::$defaultPostInput = json_encode([
			'user' => $this->accountName,
			'timestamps' => $this->timestamps
		]);
		$obj = new MessageSetRead();
		$obj->exec();
		$this->assertDataMock('setMessagesAsRead', [$this->studyId, $this->accountName, $this->timestamps]);
	}
	
	function test_with_missing_data() {
		Main::$defaultPostInput = json_encode([
			'user' => $this->accountName
		]);
		$obj = new MessageSetRead();
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
	
	function test_with_faulty_data() {
		Main::$defaultPostInput = '';
		$obj = new MessageSetRead();
		$this->expectException(PageFlowException::class);
		$obj->exec();
	}
}