<?php

namespace test\backend\admin\features\readPermission;

use backend\admin\features\readPermission\GetMedia;
use backend\subStores\ResponsesStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseReadPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class GetMediaTest extends BaseReadPermissionTestSetup {
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$this->createStoreMock(
			'getResponsesStore',
			$this->createDataMock(ResponsesStore::class, 'outputImageFromResponses'),
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$userId = 'userId';
		$entryId = 678;
		$key = 'key';
		$this->setGet([
			'userId' => $userId,
			'entryId' => $entryId,
			'key' => $key,
			'media_type' => 'image'
		]);
		$obj = new GetMedia();
		
		$obj->execAndOutput();
		$this->assertDataMock('outputImageFromResponses', [$this->studyId, $userId, $entryId, $key]);
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(GetMedia::class, [
			'userId' => 'userId',
			'entryId' => 'entryId',
			'key' => 'key',
			'media_type' => 'image'
		], true, true);
	}
}