<?php

namespace test\backend\admin\features\messagePermission;

use backend\admin\features\messagePermission\ListMessages;
use backend\subStores\MessagesStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseMessagePermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class ListMessagesTest extends BaseMessagePermissionTestSetup {
	private $accountName = 'user';
	private $messagesListContent = ['entry1', 'entry2'];
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getMessagesStore',
			$this->createDataMock(MessagesStore::class, 'getMessagesList', $this->messagesListContent),
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$this->setGet(['study_id' => $this->studyId, 'user' => $this->accountName]);
		$obj = new ListMessages();
		$obj->exec();
		$this->assertDataMock('getMessagesList', [$this->studyId, $this->accountName]);
	}
	
	function test_with_missing_data() {
		$this->setPost();
		$this->assertMissingDataForFeatureObj(ListMessages::class, [
			'study_id' => $this->studyId,
			'user' => 'user'
		], true);
	}
}