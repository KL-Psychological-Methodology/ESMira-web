<?php

namespace test\backend\admin\features\readPermission;

use backend\admin\features\readPermission\GetData;
use backend\subStores\ResponsesStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseReadPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class GetDataTest extends BaseReadPermissionTestSetup {
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getResponsesStore',
			$this->createDataMock(ResponsesStore::class, 'outputResponsesFile'),
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$qid = 'id';
		$this->setGet([
			'q_id' => $qid
		]);
		$obj = new GetData();
		
		$obj->execAndOutput();
		$this->assertDataMock('outputResponsesFile', [$this->studyId, $qid]);
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(GetData::class, [
			'q_id' => 'we'
		], true, true);
	}
}