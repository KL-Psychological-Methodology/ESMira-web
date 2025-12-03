<?php

namespace backend\admin\features\messagePermission;

use backend\admin\features\messagePermission\ListUserWithMessages;
use backend\subStores\MessagesStore;
use PHPUnit\Framework\MockObject\Stub;
use testConfigs\BaseMessagePermissionTestSetup;

require_once __DIR__ . '/../../../../autoload.php';

class ListUserWithMessagesTest extends BaseMessagePermissionTestSetup {
	private $messagesListContent = ['entry1', 'entry2'];
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getMessagesStore',
			$this->createDataMock(MessagesStore::class, 'getParticipantsWithMessages', $this->messagesListContent),
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$obj = new ListUserWithMessages();
		$obj->exec();
		$this->assertDataMock('getParticipantsWithMessages', [$this->studyId]);
	}
}