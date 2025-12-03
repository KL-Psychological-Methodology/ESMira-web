<?php

namespace backend\admin\features\messagePermission;

use backend\admin\features\messagePermission\ListParticipants;
use backend\subStores\StudyStore;
use PHPUnit\Framework\MockObject\Stub;
use testConfigs\BaseMessagePermissionTestSetup;

require_once __DIR__ . '/../../../../autoload.php';

class ListParticipantsTest extends BaseMessagePermissionTestSetup {
	private $messagesListContent = ['entry1', 'entry2'];
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getStudyStore',
			$this->createDataMock(StudyStore::class, 'getStudyParticipants', $this->messagesListContent),
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$obj = new ListParticipants();
		$obj->exec();
		$this->assertDataMock('getStudyParticipants', [$this->studyId]);
	}
}