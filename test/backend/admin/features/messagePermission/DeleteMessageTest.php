<?php

namespace backend\admin\features\messagePermission;

use backend\admin\features\messagePermission\DeleteMessage;
use backend\subStores\MessagesStore;
use PHPUnit\Framework\MockObject\Stub;
use testConfigs\BaseMessagePermissionTestSetup;

require_once __DIR__ . '/../../../../autoload.php';

class DeleteMessageTest extends BaseMessagePermissionTestSetup {
	private $user = 'user';
	private $sentTimestamp = 111;
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getMessagesStore',
			$this->createDataMock(MessagesStore::class, 'deleteMessage'),
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$this->setPost(['study_id' => $this->studyId, 'userId' => $this->user, 'sent' => $this->sentTimestamp]);
		$obj = new DeleteMessage();
		$obj->exec();
		$this->assertDataMock('deleteMessage', [$this->studyId, $this->user, $this->sentTimestamp]);
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(DeleteMessage::class, [
			'study_id' => $this->studyId,
			'userId' => 'user',
			'sent' => 123,
		]);
	}
}