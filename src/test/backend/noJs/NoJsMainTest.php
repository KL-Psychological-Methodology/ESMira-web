<?php

namespace test\backend\noJs;

use backend\DataSetCache;
use backend\Main;
use backend\noJs\ForwardingException;
use backend\noJs\NoJsMain;
use backend\noJs\pages\StudiesList;
use backend\noJs\StudyData;
use backend\ResponsesIndex;
use backend\subStores\ResponsesStore;
use backend\subStores\StudyAccessIndexStore;
use backend\subStores\StudyStore;
use backend\subStores\UserDataStore;
use PHPUnit\Framework\MockObject\Stub;
use stdClass;
use test\testConfigs\BaseMockedTestSetup;
use test\testConfigs\SkipArgument;

require_once __DIR__ .'/../../../backend/autoload.php';

class NoJsMainTest extends BaseMockedTestSetup {
	private $studyConfig = [
		111 => ['id' => 111, 'questionnaires' => [['internalId' => 1111]]],
		222 => ['id' => 222, 'questionnaires' => [['internalId' => 2222]]],
		333 => ['id' => 333, 'questionnaires' => [['internalId' => 3333]]],
		444 => ['id' => 444, 'questionnaires' => [['internalId' => 4444]]],
		555 => ['id' => 555, 'questionnaires' => [['internalId' => 5555]]],
	];
	private $studyIndex = [
		'' => [111, 333],
		'key2' => [222],
		'key3' => [333, 555]
	];
	private $questionnaireIdIndex = [
		1111 => 111,
		2222 => 222,
		3333 => 333,
		4444 => 444,
		5555 => 555
	];
	private $eventIndexKey = 'indexKey';
	
	public function setUp(): void {
		parent::setUp();
		Main::setCookie('access_key', '');
	}
	
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$studyStore = $this->createStub(StudyStore::class);
		$studyStore->method('getStudyLangConfig')
			->willReturnCallback(function(int $studyId): stdClass {
				return $this->getExpectedStudyConfig($studyId);
			});
		$studyStore->method('getEventIndex') //needed so CreateDataSet is able to call saveDataSetCache()
			->willReturn(new ResponsesIndex([$this->eventIndexKey]));
		$this->createStoreMock('getStudyStore', $studyStore, $observer);
		
		
		$studyAccessIndexStore = $this->createStub(StudyAccessIndexStore::class);
		$studyAccessIndexStore->method('getStudyIdForQuestionnaireId')
			->willReturnCallback(function($qId): int {
				return $this->questionnaireIdIndex[$qId] ?? -1;
			});
		$studyAccessIndexStore->method('getStudyIds')
			->willReturnCallback(function($key): array {
				return $this->studyIndex[$key] ?? [];
			});
		$this->createStoreMock('getStudyAccessIndexStore', $studyAccessIndexStore, $observer);
		
		
		$studyStore = $this->createStub(UserDataStore::class); //needed so CreateDataSet is able to call saveDataSetCache()
		$studyStore->method('addDataSetForSaving')
			->willReturn(true);
		$this->createStoreMock('getUserDataStore', $studyStore, $observer);
		
		
		$this->createStoreMock(
			'getResponsesStore',
			$this->createDataMock(ResponsesStore::class, 'saveDataSetCache', function(string $userId, DataSetCache $cache, callable $success, callable $error) {
				$success(0);
			}),
			$observer
		);
		
		return $observer;
	}
	
	private function getExpectedStudyConfig(int $studyId): stdClass {
		return json_decode(json_encode($this->studyConfig[$studyId]));
	}
	
	public function test_questionnaireIsActive() {
		$this->assertTrue(NoJsMain::questionnaireIsActive((object) ['pages' => [[]], 'publishedWeb' => true]));
		$this->assertFalse(NoJsMain::questionnaireIsActive((object) ['pages' => [[]], 'publishedWeb' => false]));
		$this->assertTrue(NoJsMain::questionnaireIsActive((object) ['pages' => [[]], 'durationStart' => time()]));
		$this->assertFalse(NoJsMain::questionnaireIsActive((object) ['pages' => [[]], 'durationStart' => time()+1]));
		$this->assertTrue(NoJsMain::questionnaireIsActive((object) ['pages' => [[]], 'durationEnd' => time()]));
		$this->assertFalse(NoJsMain::questionnaireIsActive((object) ['pages' => [[]], 'durationEnd' => time()-1]));
		
		$this->assertFalse(NoJsMain::questionnaireIsActive((object) ['pages' => [], 'publishedWeb' => true]));
		$this->assertFalse(NoJsMain::questionnaireIsActive((object) ['publishedWeb' => true]));
	}
	
	public function test_getQuestionnaire() {
		$study = (object)[
			'questionnaires' => [
				(object) ['internalId' => 1111],
				(object) ['internalId' => 2222],
				(object) ['internalId' => 3333],
				(object) ['internalId' => 4444],
				(object) ['internalId' => 5555]
			]
		];
		$this->assertEquals((object) ['internalId' => 1111], NoJsMain::getQuestionnaire($study, 1111));
		$this->assertEquals((object) ['internalId' => 3333], NoJsMain::getQuestionnaire($study, 3333));
		$this->assertEquals((object) ['internalId' => 5555], NoJsMain::getQuestionnaire($study, 5555));
		$this->assertNull(NoJsMain::getQuestionnaire($study, 6666));
		
		$this->assertEquals((object) ['internalId' => 1111], NoJsMain::getQuestionnaire((object)[
			'questionnaires' => [
				(object) ['internalId' => 1111]
			]
		], 1111));
		$this->assertNull(NoJsMain::getQuestionnaire((object)[
			'questionnaires' => []
		], 1111));
	}
	
	//getStudyData() without ids:
	public function test_getStudyData_with_nothing() {
		$this->setGet();
		$this->expectErrorMessage('Wrong access key.');
		NoJsMain::getStudyData();
	}
	public function test_getStudyData_with_not_existing_accessKey() {
		$this->setGet([
			'key' => 'wrong'
		]);
		$this->expectErrorMessage('Wrong access key.');
		NoJsMain::getStudyData();
	}
	public function test_getStudyData_without_ids_and_too_many_studies_available() {
		$this->setGet([
			'key' => 'key3'
		]);
		$this->expectErrorMessage('Wrong access key.');
		NoJsMain::getStudyData();
	}
	public function test_getStudyData_without_ids() {
		$this->setGet([
			'key' => 'key2'
		]);
		$studyData = NoJsMain::getStudyData();
		$this->assertEquals(new StudyData('key2', $this->getExpectedStudyConfig(222)), $studyData);
	}
	
	//getStudyData() with only studyId:
	public function test_getStudyData_with_studyId() {
		$this->setGet([
			'id' => 111
		]);
		$studyData = NoJsMain::getStudyData();
		$this->assertEquals(new StudyData('', $this->getExpectedStudyConfig(111)), $studyData);
	}
	public function test_getStudyData_with_not_existing_studyId() {
		$this->setGet([
			'id' => 999
		]);
		$this->expectErrorMessage('Wrong access key.');
		NoJsMain::getStudyData();
	}
	public function test_getStudyData_with_studyId_and_unneeded_accessKey() {
		$this->setGet([
			'key' => 'key2',
			'id' => 111
		]);
		$this->expectErrorMessage('Wrong access key.');
		NoJsMain::getStudyData();
	}
	public function test_getStudyData_with_studyId_and_accessKey() {
		$this->setGet([
			'key' => 'key3',
			'id' => 555
		]);
		$studyData = NoJsMain::getStudyData();
		$this->assertEquals(new StudyData('key3', $this->getExpectedStudyConfig(555)), $studyData);
	}
	public function test_getStudyData_with_studyId_and_wrong_accessKey() {
		$this->setGet([
			'key' => 'key2',
			'id' => 555
		]);
		$this->expectErrorMessage('Wrong access key.');
		NoJsMain::getStudyData();
	}
	public function test_getStudyData_with_studyId_and_not_existing_accessKey() {
		$this->setGet([
			'key' => 'notExisting',
			'id' => 555
		]);
		$this->expectErrorMessage('Wrong access key.');
		NoJsMain::getStudyData();
	}
	
	//getStudyData() with only questionnaireId:
	public function test_getStudyData_with_questionnaireId() {
		$this->setGet([
			'qid' => 1111
		]);
		$studyData = NoJsMain::getStudyData();
		$this->assertEquals(new StudyData('', $this->getExpectedStudyConfig(111), (object) ['internalId' => 1111]), $studyData);
	}
	public function test_getStudyData_with_not_existing_questionnaireId() {
		$this->setGet([
			'qid' => 9999
		]);
		$this->expectErrorMessage('Wrong access key.');
		NoJsMain::getStudyData();
	}
	public function test_getStudyData_with_questionnaireId_and_unneeded_accessKey() {
		$this->setGet([
			'key' => 'key2',
			'qid' => 1111
		]);
		$this->expectErrorMessage('Wrong access key.');
		NoJsMain::getStudyData();
	}
	public function test_getStudyData_with_questionnaireId_and_accessKey() {
		$this->setGet([
			'key' => 'key3',
			'qid' => 3333
		]);
		$studyData = NoJsMain::getStudyData();
		$this->assertEquals(new StudyData('key3', $this->getExpectedStudyConfig(333), (object) ['internalId' => 3333]), $studyData);
	}
	public function test_getStudyData_with_questionnaireId_and_wrong_accessKey() {
		$this->setGet([
			'key' => 'key2',
			'qid' => 3333
		]);
		$this->expectErrorMessage('Wrong access key.');
		NoJsMain::getStudyData();
	}
	public function test_getStudyData_with_questionnaireId_and_not_existing_accessKey() {
		$this->setGet([
			'key' => 'notExisting',
			'qid' => 3333
		]);
		$this->expectErrorMessage('Wrong access key.');
		NoJsMain::getStudyData();
	}
	
	//getStudyData() with studyId and questionnaireId:
	public function test_getStudyData_with_studyId_and_questionnaireId() {
		$this->setGet([
			'id' => 111,
			'qid' => 1111
		]);
		$studyData = NoJsMain::getStudyData();
		$this->assertEquals(new StudyData('', $this->getExpectedStudyConfig(111), (object) ['internalId' => 1111]), $studyData);
	}
	public function test_getStudyData_with_studyId_and_wrong_questionnaireId() {
		$this->setGet([
			'id' => 111,
			'qid' => 2222
		]);
		$studyData = NoJsMain::getStudyData();
		$this->assertEquals(new StudyData('', $this->getExpectedStudyConfig(111)), $studyData);
	}
	public function test_getStudyData_with_studyId_and_not_existing_questionnaireId() {
		$this->setGet([
			'id' => 111,
			'qid' => 9999
		]);
		$studyData = NoJsMain::getStudyData();
		$this->assertEquals(new StudyData('', $this->getExpectedStudyConfig(111)), $studyData);
	}
	public function test_getStudyData_with_studyId_and_questionnaireId_and_unneeded_accessKey() {
		$this->setGet([
			'key' => 'key2',
			'id' => 111,
			'qid' => 1111
		]);
		$this->expectErrorMessage('Wrong access key.');
		NoJsMain::getStudyData();
	}
	public function test_getStudyData_with_studyId_and_questionnaireId_and_accessKey() {
		$this->setGet([
			'key' => 'key2',
			'id' => 222,
			'qid' => 2222
		]);
		$studyData = NoJsMain::getStudyData();
		$this->assertEquals(new StudyData('key2', $this->getExpectedStudyConfig(222), (object) ['internalId' => 2222]), $studyData);
	}
	public function test_getStudyData_with_studyId_and_questionnaireId_and_wrong_accessKey() {
		$this->setGet([
			'key' => 'key2',
			'id' => 333,
			'qid' => 3333
		]);
		$this->expectErrorMessage('Wrong access key.');
		NoJsMain::getStudyData();
	}
	public function test_getStudyData_with_studyId_and_questionnaireId_and_not_existing_accessKey() {
		$this->setGet([
			'key' => 'notExisting',
			'id' => 333,
			'qid' => 3333
		]);
		$this->expectErrorMessage('Wrong access key.');
		NoJsMain::getStudyData();
	}
}