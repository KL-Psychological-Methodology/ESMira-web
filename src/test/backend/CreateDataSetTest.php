<?php
declare(strict_types=1);

namespace test\backend;

use backend\Configs;
use backend\CreateDataSet;
use backend\exceptions\CriticalException;
use backend\dataClasses\StudyStatisticsMetadataEntry;
use backend\dataClasses\StudyStatisticsEntry;
use backend\DataSetCache;
use backend\DataSetCacheContainer;
use backend\DataStoreInterface;
use backend\Main;
use backend\Paths;
use backend\ResponsesIndex;
use backend\subStores\ResponsesStore;
use backend\subStores\ServerStatisticsStore;
use backend\subStores\StatisticsStoreWriter;
use backend\subStores\StudyMetadataStore;
use backend\subStores\StudyStatisticsMetadataStore;
use backend\subStores\StudyStore;
use backend\subStores\UserDataStore;
use Exception;
use test\testConfigs\BaseTestSetup;
use const backend\ONE_DAY;

require_once __DIR__ .'/../../backend/autoload.php';


class CreateDataSetTest extends BaseTestSetup {
	private $studyId = 123;
	private $questionnaireId = 123456;
	private $dataSetId = 111;
	/**
	 * @var string[]
	 */
	private $accessKeys = ['key1', 'key2'];
	private $userId = 'user1';
	private $appVersion = '0.0';
	private $appType = 'UnitTest';
	private $responseTime = '1114313512000';
	/**
	 * @var DataStoreInterface
	 */
	private $dataStoreObserver;
	/**
	 * @var StudyStore
	 */
	private $studyStoreObserver;
	
	private $userIdWithTooManyRequests = 'tooMany';
	private $userIdWithOutdatedToken = 'isOutdated';
	private $userIdNewUser = 'newUser';
	
	
	function setUp(): void {
		$this->dataStoreObserver = $this->createMock(DataStoreInterface::class);
		
		
		//getStudyMetadata() returns Stub when studyId is correct
		$this->dataStoreObserver
			->method('getStudyMetadataStore')
			->willReturnCallback(function($studyId): StudyMetadataStore {
				if($studyId != $this->studyId)
					throw new \backend\exceptions\CriticalException("Study $studyId does not exist");
				$metadata = $this->createStub(StudyMetadataStore::class);
				$metadata->expects($this->once())
					->method('getAccessKeys')
					->will($this->returnValue($this->accessKeys));
				return $metadata;
			});
		
		
		$this->dataStoreObserver
			->method('getUserDataStore')
			->will($this->returnCallback(function(string $userId) {
				$userTokenMock = $this->createMock(UserDataStore::class);
				$userTokenMock
					->method('addDataSetForSaving')
					->with(
						$this->equalTo($this->studyId),
						$this->equalTo(0), //group
						$this->anything(),
						$this->anything()
					)
					->will($this->returnValue($userId != $this->userIdWithTooManyRequests));
				
				$userTokenMock
					->method('isOutdated')
					->with(
						$this->equalTo($this->studyId),
						$this->anything(),
						$this->anything()
					)
					->will($this->returnValue($userId == $this->userIdWithOutdatedToken));
				
				$userTokenMock
					->method('isNewUser')
					->will($this->returnValue($userId == $this->userIdNewUser));
				
				$userTokenMock
					->method('countNewUser')
					->will($this->returnValue($userId == $this->userIdNewUser ? 1 : 0));
				
				$userTokenMock->expects($this->once())
					->method('writeAndClose');
				return $userTokenMock;
			}));
		
		$this->studyStoreObserver = $this->createStub(StudyStore::class);
		$this->studyStoreObserver->method('getEventIndex')
			->will($this->returnValue(new ResponsesIndex(KEYS_EVENT_RESPONSES)));
		
		$this->dataStoreObserver
			->method('getStudyStore')
			->willReturnCallback(function() {
				return $this->studyStoreObserver;
			});
	}
	protected function tearDown(): void {
		Configs::resetAll();
	}
	
	private function checkIds(DataSetCacheContainer $cacheArray, callable $successCallback) {
		$this->assertEquals([$this->dataSetId], $cacheArray->ids);
		foreach($cacheArray->ids as $datasetId) {
			$successCallback($datasetId);
		}
	}
	private function getCacheFromQuestionnaireData(DataSetCache $cache) {
		$questionnaireCache = $cache->getQuestionnaireCache();
		$this->assertArrayHasKey($this->studyId, $questionnaireCache, "eventCache does not have expected entry: $this->studyId\n" .print_r($questionnaireCache, true));
		
		$studyCache = $questionnaireCache[$this->studyId];
		$this->assertArrayHasKey($this->questionnaireId, $studyCache, "studyCache does not have expected entry: $this->questionnaireId\n" .print_r($questionnaireCache, true));
		return $studyCache[$this->questionnaireId];
	}
	private function createObserverForSaveDataSetCache(array $source, callable $callbackGetCacheArray) {
		$responsesStore = $this->createMock(ResponsesStore::class);
		$responsesStore->expects($this->once())
			->method('saveDataSetCache')
			->willReturnCallback(function(
				string       $userId,
				DataSetCache $cache,
				callable     $successCallback,
				callable     $errorCallback
			) use ($source, $callbackGetCacheArray) {
				$cacheArray = $callbackGetCacheArray($cache);
				$data = $cacheArray->data[0];
				foreach($source as $key => $value) {
					$this->assertArrayHasKey($key, $data, print_r($data, true));
					$this->assertEquals($value, $data[$key], "assertEquals for $key failed.");
				}
				
				$this->checkIds($cacheArray, $successCallback);
			});
		$this->dataStoreObserver->method('getResponsesStore')->willReturn($responsesStore);
		
	}
	
	private function createObserverForQuestionnaireEvent($source, $types = []) {
		$this->studyStoreObserver
			->method('getQuestionnaireIndex')
			->will($this->returnValue(new ResponsesIndex(array_keys($source), $types)));
		
		//questionnaireExists() returns true when studyId is correct
		$this->studyStoreObserver
			->method('questionnaireExists')
			->will($this->returnCallback(function($studyId, $questionnaireId) {
				return $studyId == $this->studyId && $questionnaireId == $this->questionnaireId;
			}));
	}
	private function creaseObserverForStatistics(string $appType) {
		$this->dataStoreObserver->expects($this->once())
			->method('getServerStatisticsStore')
			->willReturnCallback(function() use($appType) {
				$statisticsStore = $this->createMock(ServerStatisticsStore::class);
				$statisticsStore->expects($this->once())
					->method('update')
					->willReturnCallback(function(callable $changeCallback) use($appType) {
						$statisticsStoreWriter = $this->createMock(StatisticsStoreWriter::class);
						$statisticsStoreWriter->expects($this->once())
							->method('incrementUser');
						
						$statisticsStoreWriter->expects($appType == 'Android' ? $this->once() : $this->any())
							->method('incrementAndroid');
						
						$statisticsStoreWriter->expects($appType == 'iOS' ? $this->once() : $this->any())
							->method('incrementIos');
						
						$statisticsStoreWriter->expects($appType == 'Web' ? $this->once() : $this->any())
							->method('incrementWeb');
						
						$startOfDay = (int) (floor(time() / ONE_DAY) * ONE_DAY);
						$statisticsStoreWriter->expects($this->once())
							->method('addDataToDay')
							->with(
								$startOfDay - ONE_DAY * Configs::get('number_of_saved_days_in_server_statistics'),
								$startOfDay,
								$appType,
								$this->appVersion,
								0,
								1
							);
						$changeCallback($statisticsStoreWriter);
					});
				return $statisticsStore;
			});
	}
	
	private function assertNoErrors(CreateDataSet $dataset) {
		$this->assertCount(1, $dataset->output);
		$output = $dataset->output[0];
		$this->assertEquals($this->dataSetId, $output['dataSetId']);
		$this->assertTrue($output['success']);
	}
	private function assertHasErrors(CreateDataSet $dataset, string $needle) {
		$output = $dataset->output[0];
		$this->assertEquals($this->dataSetId, $output['dataSetId']);
		$this->assertFalse($output['success']);
		$this->assertStringContainsString($needle, $output['error']);
	}
	
	
	private function createDataSetObj(array $datasetObj, array $json = []): CreateDataSet {
		Configs::injectDataStore($this->dataStoreObserver);
		
		$dataset = new CreateDataSet((object) array_merge([
			'userId' => $this->userId,
			'appType' => $this->appType,
			'appVersion' => $this->appVersion,
			'serverVersion' => (string) Main::ACCEPTED_SERVER_VERSION,
			'dataset' => [
				(object) array_merge([
					'dataSetId' => (string) $this->dataSetId,
					'studyId' => (string) $this->studyId,
					'questionnaireInternalId' => (string) $this->questionnaireId,
					'accessKey' => $this->accessKeys[0],
					'responseTime' => $this->responseTime,
					'responses' => (object) []
				], $datasetObj)
			]
		], $json));
		$dataset->exec();
		
		return $dataset;
	}
	private function getSourceData(array $additional): array {
		return array_merge([
			'studyId' => $this->studyId,
			'userId' => $this->userId,
			'accessKey' => $this->accessKeys[0],
			'responseTime' => $this->responseTime,
			'appType' => $this->appType,
			'appVersion' => $this->appVersion,
		], $additional);
	}
	
	
	function test_updateServerStatistics_with_android() {
		$appType = 'Android';
		$this->creaseObserverForStatistics($appType);
		
		$this->createDataSetObj([
			'eventType' => 'joined',
		], [
			'userId' => $this->userIdNewUser,
			'appType' => $appType
		]);
	}
	function test_updateServerStatistics_with_iOS() {
		$appType = 'iOS';
		$this->creaseObserverForStatistics($appType);
		
		$this->createDataSetObj([
			'eventType' => 'joined',
		], [
			'userId' => $this->userIdNewUser,
			'appType' => $appType
		]);
	}
	function test_updateServerStatistics_with_web() {
		$appType = 'Web';
		$this->creaseObserverForStatistics($appType);
		
		$this->createDataSetObj([
			'eventType' => 'joined',
		], [
			'userId' => $this->userIdNewUser,
			'appType' => $appType
		]);
	}
	
	function test_with_joined_event() {
		$source = $this->getSourceData([
			'eventType' => 'joined',
			'model' => 'Unit tester',
			'manufacturer' => 'JodliDev',
			'osVersion' => '1'
		]);
		
		$this->createObserverForSaveDataSetCache($source, function(DataSetCache $cache) use ($source){
			$eventCache = $cache->getEventCache();
			$this->assertArrayHasKey($this->studyId, $eventCache, "Cache does not have expected entry: $this->studyId\n" .print_r($eventCache, true));
			return $eventCache[$this->studyId];
		});
		
		$dataset = $this->createDataSetObj([
			'eventType' => $source['eventType'],
			'responses' => (object)[
				'model' => $source['model'],
				'manufacturer' => $source['manufacturer'],
				'osVersion' => $source['osVersion']
			]
		]);
		
		$this->assertNoErrors($dataset);
	}
	
	function test_with_quit_event() {
		$source = $this->getSourceData([
			'eventType' => 'quit',
			'model' => 'Unit tester',
			'manufacturer' => 'JodliDev',
			'osVersion' => '1'
		]);
		
		
		$this->createObserverForSaveDataSetCache($source, function(DataSetCache $cache) use ($source){
			$eventCache = $cache->getEventCache();
			$this->assertArrayHasKey($this->studyId, $eventCache, "Cache does not have expected entry: $this->studyId\n" .print_r($eventCache, true));
			return $eventCache[$this->studyId];
		});
		
		$dataset = $this->createDataSetObj([
			'eventType' => $source['eventType'],
			'responses' => (object)[
				'model' => $source['model'],
				'manufacturer' => $source['manufacturer'],
				'osVersion' => $source['osVersion']
			]
		]);
		$this->assertNoErrors($dataset);
	}
	
	function test_with_questionnaire_event() {
		$source = $this->getSourceData([
			'eventType' => 'questionnaire',
			'actionScheduledTo' => '626637180000',
			'lastInvitation' => '626637180000',
			'model' => 'Unit tester',
			'manufacturer' => 'JodliDev',
			'osVersion' => '1',
			'key1' => 'response1'
		]);
		
		$this->createObserverForQuestionnaireEvent($source);
		$this->createObserverForSaveDataSetCache($source, function(DataSetCache $cache) use ($source) {
			return $this->getCacheFromQuestionnaireData($cache);
		});
		
		$dataset = $this->createDataSetObj([
			'questionnaireInternalId' => $this->questionnaireId,
			'eventType' => $source['eventType'],
			'responses' => (object)[
				'actionScheduledTo' => $source['actionScheduledTo'],
				'lastInvitation' => $source['lastInvitation'],
				'model' => $source['model'],
				'manufacturer' => $source['manufacturer'],
				'osVersion' => $source['osVersion'],
				'key1' => $source['key1']
			]
		]);
		
		$this->assertNoErrors($dataset);
	}
	
	function test_with_statistics() {
		$statisticsKey = 'statisticsKey1';
		$statisticsValue = '5';
		$source = $this->getSourceData([
			'eventType' => 'questionnaire',
			$statisticsKey => $statisticsValue
		]);
		
		$this->createObserverForQuestionnaireEvent($source);
		
		$this->dataStoreObserver->expects($this->once())
			->method('getStudyStatisticsMetadataStore')
			->willReturnCallback(function(int $studyId) use($statisticsKey, $statisticsValue) {
				if($studyId != $this->studyId)
					throw new Exception("getStudyStatisticsMetadataStore() was used with wrong studyId: $studyId");
				
				$stub = $this->createStub(StudyStatisticsMetadataStore::class);
				$stub->method('loadMetadataCollection')
					->will($this->returnValue([
						$statisticsKey => [
							new StudyStatisticsEntry( //supposed to be false
								[
									(object) [
										'key' => $statisticsKey,
										'operator' => CreateDataSet::CONDITION_OPERATOR_GREATER,
										'value' => $statisticsValue-1
									],
									(object) [
										'key' => $statisticsKey,
										'operator' => CreateDataSet::CONDITION_OPERATOR_LESS,
										'value' => $statisticsValue-1
									]
								],
								CreateDataSet::CONDITION_TYPE_AND, //false because of and
								CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
								10
							),
							new StudyStatisticsEntry( //supposed to be false
								[
									(object) [
										'key' => $statisticsKey,
										'operator' => CreateDataSet::CONDITION_OPERATOR_UNEQUAL,
										'value' => $statisticsValue
									]
								],
								CreateDataSet::CONDITION_TYPE_AND,
								CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
								10
							),
							
							
							new StudyStatisticsEntry( //supposed to be true
								[],
								CreateDataSet::CONDITION_TYPE_ALL,
								CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
								10
							),
							new StudyStatisticsEntry( //supposed to be true
								[],
								CreateDataSet::CONDITION_TYPE_AND,
								CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
								10
							),
							new StudyStatisticsEntry( //supposed to be true
								[
									(object) [
										'key' => $statisticsKey,
										'operator' => CreateDataSet::CONDITION_OPERATOR_EQUAL,
										'value' => $statisticsValue
									],
									(object) [
										'key' => $statisticsKey,
										'operator' => CreateDataSet::CONDITION_OPERATOR_UNEQUAL,
										'value' => $statisticsValue
									]
								],
								CreateDataSet::CONDITION_TYPE_OR, //true because of or
								CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
								10
							)
						]
					]));
				return $stub;
			});
		
		$responsesStore = $this->createMock(ResponsesStore::class);
		$responsesStore->expects($this->once())
			->method('saveDataSetCache')
			->willReturnCallback(function(
				string       $userId,
				DataSetCache $cache,
				callable     $successCallback,
				callable     $errorCallback
			) use ($source, $statisticsKey, $statisticsValue) {
				$statisticsCacheEntry = $cache->getStatisticsCache()[$this->studyId];
				$this->assertCount(3, $statisticsCacheEntry->data);
				
				$this->assertEquals(2, $statisticsCacheEntry->data[0]->index);
				$this->assertEquals($statisticsKey, $statisticsCacheEntry->data[0]->key);
				$this->assertEquals($statisticsValue, $statisticsCacheEntry->data[0]->answer);
				
				$this->assertEquals(3, $statisticsCacheEntry->data[1]->index);
				$this->assertEquals($statisticsKey, $statisticsCacheEntry->data[1]->key);
				$this->assertEquals($statisticsValue, $statisticsCacheEntry->data[1]->answer);
				
				$this->assertEquals(4, $statisticsCacheEntry->data[2]->index);
				$this->assertEquals($statisticsKey, $statisticsCacheEntry->data[2]->key);
				$this->assertEquals($statisticsValue, $statisticsCacheEntry->data[2]->answer);
				
				$this->checkIds($statisticsCacheEntry, $successCallback);
			});
		$this->dataStoreObserver->method('getResponsesStore')->willReturn($responsesStore);
		
		$dataset = $this->createDataSetObj([
			'questionnaireInternalId' => $this->questionnaireId,
			'eventType' => $source['eventType'],
			'responses' => (object)[
				$statisticsKey => $source[$statisticsKey]
			]
		]);
		$this->assertNoErrors($dataset);
	}
	
	function test_with_image_item() {
		$itemKey = 'imageKey';
		$source = $this->getSourceData([
			'eventType' => 'questionnaire',
			'model' => 'Unit tester',
			'manufacturer' => 'JodliDev',
			'osVersion' => '1',
			$itemKey => '987'
		]);
		
		$this->createObserverForQuestionnaireEvent($source, [
			$itemKey => 'image'
		]);
		$responsesStore = $this->createMock(ResponsesStore::class);
		$responsesStore->expects($this->once())
			->method('saveDataSetCache')
			->willReturnCallback(function(
				string       $userId,
				DataSetCache $cache,
				callable     $successCallback,
				callable     $errorCallback
			) use ($source, $itemKey) {
				$cacheArray = $this->getCacheFromQuestionnaireData($cache);
				
				$data = $cacheArray->data[0];
				
				$this->assertEquals(
					Paths::publicFileImageFromData($this->userId, 0, $itemKey),
					$data[$itemKey]
				);
				
				$this->checkIds($cacheArray, $successCallback);
			});
		$this->dataStoreObserver->method('getResponsesStore')->willReturn($responsesStore);
		
		$dataset = $this->createDataSetObj([
			'questionnaireInternalId' => $this->questionnaireId,
			'eventType' => $source['eventType'],
			'responses' => (object)[
				'model' => $source['model'],
				'manufacturer' => $source['manufacturer'],
				'osVersion' => $source['osVersion'],
				$itemKey => $source[$itemKey]
			]
		]);
		
		$this->assertNoErrors($dataset);
	}
	
	function test_with_wrong_accessKey() {
		$accessKey = 'wrong accessKey';
		
		$dataset = $this->createDataSetObj([
			'eventType' => 'joined',
			'accessKey' => $accessKey,
			'responseTime' => Main::getMilliseconds()
		]);
		
		$this->assertHasErrors($dataset, $accessKey);
	}
	
	function test_with_wrong_studyId() {
		$studyId = 2222;
		
		$dataset = $this->createDataSetObj([
			'studyId' => $studyId,
			'eventType' => 'joined'
		]);
		
		$this->assertHasErrors($dataset, (string) $studyId);
	}
	
	function test_with_wrong_questionnaireId() {
		$questionnaireId = 444;
		
		$dataset = $this->createDataSetObj([
			'questionnaireInternalId' => $questionnaireId,
			'eventType' => 'questionnaire'
		]);
		
		$this->assertHasErrors($dataset, (string) $questionnaireId);
	}
	
	function test_with_too_many_requests() {
		$dataset = $this->createDataSetObj([
			'eventType' => 'joined'
		], [
			'userId' => $this->userIdWithTooManyRequests
		]);
		
		$this->assertHasErrors($dataset, 'Too many requests in succession');
	}
	
	function test_with_outdated_token() {
		$responsesStore = $this->createMock(ResponsesStore::class);
		$responsesStore->expects($this->once())
			->method('saveDataSetCache')
			->willReturnCallback(function(
				string       $userId,
				DataSetCache $cache,
				callable     $successCallback,
				callable     $errorCallback
			) {
				$this->assertEmpty($cache->getStatisticsCache());
				$this->assertEmpty($cache->getEventCache());
				$this->assertEmpty($cache->getQuestionnaireCache());
				$this->assertEmpty($cache->getFileCache());
			});
		$this->dataStoreObserver->method('getResponsesStore')->willReturn($responsesStore);
		
		$dataset = $this->createDataSetObj([
			'eventType' => 'joined',
			'token' => '999',
			'reupload' => true
		], [
			'userId' =>$this->userIdWithOutdatedToken
		]);
		
		$this->assertNoErrors($dataset);
	}
	
	function test_with_saving_error() {
		$errorMsg = 'test error';
		
		$responsesStore = $this->createMock(ResponsesStore::class);
		$responsesStore->expects($this->once())
			->method('saveDataSetCache')
			->willReturnCallback(function(
				string       $userId,
				DataSetCache $cache,
				callable     $successCallback,
				callable     $errorCallback
			) use ($errorMsg) {
				$errorCallback($this->dataSetId, $errorMsg);
			});
		$this->dataStoreObserver->method('getResponsesStore')->willReturn($responsesStore);
		
		$dataset = $this->createDataSetObj([
			'eventType' => 'joined'
		], [
			'userId' => $this->userIdWithOutdatedToken
		]);
		
		$this->assertHasErrors($dataset, $errorMsg);
	}
	
	function test_with_locked_study() {
		$this->studyStoreObserver
			->expects($this->once())
			->method('isLocked')
			->willReturnCallback(function(int $studyId) {
				return $studyId == $this->studyId;
			});
		
		$dataset = $this->createDataSetObj([
			'eventType' => 'joined'
		]);
		
		$this->assertHasErrors($dataset, 'Study is locked');
	}
	
	function test_with_outdated_server_version() {
		$this->expectErrorMessage('This app is outdated. Aborting');
		$this->createDataSetObj([], [
			'serverVersion' => (string) (Main::ACCEPTED_SERVER_VERSION-1)
		]);
	}
}