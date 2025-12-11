<?php

namespace backend\admin\features\adminPermission;

use backend\Configs;
use backend\SSE;
use backend\subStores\SnapshotStore;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../autoload.php';
require_once __DIR__ .'/../../../../testConfigs/variables.php';

class RestoreSnapshotDataTest extends BaseAdminPermissionTestSetup {
	private string $pathBackup = TEST_DATA_FOLDER . 'backup/';
	private string $pathUpdate = TEST_DATA_FOLDER . 'update/';
	private MockObject $snapshotStoreMock;
	private MockObject $sseMock;
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->snapshotStoreMock = $this->createMock(SnapshotStore::class);
		$this->createStoreMock('getSnapshotStore', $this->snapshotStoreMock, $observer);
		
		return $observer;
	}
	
	public function setUp(): void {
		parent::setUp();
		$this->sseMock = $this->createMock(SSE::class);
		$this->sseMock->expects($this->once())->method('sendHeader');
	}
	
	public function test_success() {
		$this->sseMock->expects($this->once())->method('flushFinished');
		$this->sseMock->expects($this->never())->method('flushFailed');
		
		mkdir($this->pathUpdate, 0777, true);
		mkdir($this->pathBackup, 0777, true);
		
		$restoreSnapshotData = new RestoreSnapshotData($this->sseMock, $this->pathUpdate, $this->pathBackup);
		$restoreSnapshotData->execAndOutput();
		
		self::assertDirectoryDoesNotExist($this->pathUpdate);
		self::assertDirectoryDoesNotExist($this->pathBackup);
	}
	
	public function test_with_exception() {
		$this->sseMock->expects($this->never())->method('flushFinished');
		$this->sseMock->expects($this->once())->method('flushFailed');
		
		$this->snapshotStoreMock->method('restoreDataFromSnapshot')
			->will($this->throwException(new Exception('Test Exception')));
		
		$restoreSnapshotData = new RestoreSnapshotData($this->sseMock, $this->pathUpdate, $this->pathBackup);
		$restoreSnapshotData->execAndOutput();
	}
}