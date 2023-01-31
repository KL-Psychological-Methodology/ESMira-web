<?php
declare(strict_types=1);

namespace test\backend\fileSystem\subStores;

require_once __DIR__ . '/../../../../backend/autoload.php';

use backend\Configs;
use backend\CreateDataSet;
use backend\exceptions\CriticalException;
use backend\DataSetCache;
use backend\DataSetCacheStatisticsEntry;
use backend\fileSystem\loader\StudyAccessKeyIndexLoader;
use backend\fileSystem\PathsFS;
use backend\fileSystem\subStores\StudyAccessIndexStoreFS;
use backend\fileSystem\subStores\StudyStoreFS;
use backend\Main;
use backend\Paths;
use backend\Permission;
use backend\ResponsesIndex;
use stdClass;
use test\testConfigs\BaseDataFolderTestSetup;

class StudyStoreFSTest extends BaseDataFolderTestSetup {
	private $studyId = 123;
	
	function tearDown(): void {
		parent::tearDown();
//		parent::tearDownAfterClass();
		try {
			$studyStore = Configs::getDataStore()->getStudyStore();
			if($studyStore->studyExists($this->studyId))
				$studyStore->delete($this->studyId);
		}
		catch(CriticalException $e) {}
	}
//	protected function setUp(): void {
//		parent::setUp();
//		parent::setUpBeforeClass();
//	}
	
	function test_studyExists() {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$this->assertFalse($studyStore->studyExists($this->studyId));
		$this->createEmptyStudy($this->studyId);
		$this->assertTrue($studyStore->studyExists($this->studyId));
	}
	
	function test_lockStudy_and_check_if_isLocked() {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$this->createEmptyStudy($this->studyId);
		
		$this->assertFalse($studyStore->isLocked($this->studyId));
		
		$studyStore->lockStudy($this->studyId, true);
		$this->assertTrue($studyStore->isLocked($this->studyId));
		
		$studyStore->lockStudy($this->studyId, false);
		$this->assertFalse($studyStore->isLocked($this->studyId));
	}
	
	function test_getStudyLastChanged() {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$now = time();
		$this->assertEquals(-1, $studyStore->getStudyLastChanged($this->studyId));
		$this->createEmptyStudy($this->studyId);
		$this->assertGreaterThanOrEqual($now, $studyStore->getStudyLastChanged($this->studyId));
	}
	
	function test_getStudyIdList() {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$this->assertEquals([], $studyStore->getStudyIdList());
		
		$this->createEmptyStudy(123);
		$this->assertEquals([123], $studyStore->getStudyIdList());
		$this->createEmptyStudy(456);
		$this->assertEquals([123, 456], $studyStore->getStudyIdList());
		$this->createEmptyStudy(789);
		$this->assertEquals([123, 456, 789], $studyStore->getStudyIdList());
		
		$studyStore->delete(123);
		$studyStore->delete(456);
		$studyStore->delete(789);
		$this->assertEquals([], $studyStore->getStudyIdList());
	}
	
	function test_getLangConfigAsJson() {
		$studyStore = Configs::getDataStore()->getStudyStore();
		
		$studyStore->saveStudy((object) [
			'_' => (object) ['id' => $this->studyId, 'lang' => '_'],
			'de' => (object) ['id' => $this->studyId, 'lang' => 'de'],
			'en' => (object) ['id' => $this->studyId, 'lang' => 'en']
		], []);
		$this->assertEquals(json_encode(['id' => $this->studyId, 'lang' => 'de']), $studyStore->getStudyLangConfigAsJson($this->studyId, 'de'));
		$this->assertEquals(json_encode(['id' => $this->studyId, 'lang' => 'en']), $studyStore->getStudyLangConfigAsJson($this->studyId, 'en'));
		$this->assertEquals(json_encode(['id' => $this->studyId, 'lang' => '_']), $studyStore->getStudyLangConfigAsJson($this->studyId, 'notExisting'));
	}
	
	function test_getStudyConfigAsJson() {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$this->createEmptyStudy($this->studyId);
		$this->assertEquals(json_encode(['id' => $this->studyId]), $studyStore->getStudyConfigAsJson($this->studyId));
	}
	
	
	function test_getStudyConfig() {
		$studyStore = Configs::getDataStore()->getStudyStore();
		
		$studyStore->saveStudy((object) [
			'_' => (object) ['id' => $this->studyId, 'lang' => '_'],
			'de' => (object) ['id' => $this->studyId, 'lang' => 'de'],
			'en' => (object) ['id' => $this->studyId, 'lang' => 'en']
		], []);
		$this->assertEquals((object) ['id' => $this->studyId, 'lang' => 'de'], $studyStore->getStudyLangConfig($this->studyId, 'de'));
		$this->assertEquals((object) ['id' => $this->studyId, 'lang' => 'en'], $studyStore->getStudyLangConfig($this->studyId, 'en'));
		$this->assertEquals((object) ['id' => $this->studyId, 'lang' => '_'], $studyStore->getStudyLangConfig($this->studyId, 'notExisting'));
	}
	
	function test_getStudyLangConfig() {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$this->createEmptyStudy($this->studyId);
		$this->assertEquals((object) ['id' => $this->studyId], $studyStore->getStudyConfig($this->studyId));
	}
	
	function test_getStudyConfig_for_not_existing_study() {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$this->expectErrorMessage("Study $this->studyId does not exist");
		$this->assertEquals(new stdClass(), $studyStore->getStudyConfig($this->studyId));
	}
	
	function test_getAllLangConfigsAsJson() {
		$studyStore = Configs::getDataStore()->getStudyStore();
		
		$studyStore->saveStudy((object) ['_' => (object) ['id' => $this->studyId], 'de' => (object) ['id' => $this->studyId]], []);
		$this->assertEquals(
			json_encode(['de' => ['id' => $this->studyId]]),
			$studyStore->getAllLangConfigsAsJson($this->studyId)
		);
	}
	
	function test_getStudyParticipants() {
		$studyStore = Configs::getDataStore()->getStudyStore();
		
		$this->createEmptyStudy($this->studyId);
		
		$this->assertEquals([], $studyStore->getStudyParticipants($this->studyId));
		
		$pathUser1 =  PathsFS::fileUserData($this->studyId, 'userId1');
		$pathUser2 =  PathsFS::fileUserData($this->studyId, 'userId2');
		$pathUser3 =  PathsFS::fileUserData($this->studyId, 'userId3');
		file_put_contents($pathUser1, 'content');
		file_put_contents($pathUser2, 'content');
		file_put_contents($pathUser3, 'content');
		
		$output = $studyStore->getStudyParticipants($this->studyId);
		sort($output); //order is undefined because filenames are hashed
		$this->assertEquals(['userId1', 'userId2', 'userId3'], $output);
	}
	
	function test_getEventIndex() {
		$this->createEmptyStudy($this->studyId);
		$this->assertEquals(new ResponsesIndex(KEYS_EVENT_RESPONSES), Configs::getDataStore()->getStudyStore()->getEventIndex($this->studyId));
	}
	function test_getQuestionnaireIndex() {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$index1 =  new ResponsesIndex(['key1', 'key2']);
		$index2 =  new ResponsesIndex(['key3']);
		
		$this->createStudy((object) [
			'id' => $this->studyId,
			'questionnaires' => [
				(object)[
					'internalId' => 123451,
					'pages' => [
						(object)[
							'inputs' => [
								(object)['name' => 'key1'],
								(object)['name' => 'key2']
							]
						]
					]
				],
				(object)[
					'internalId' => 123452,
					'pages' => [
						[
							'inputs' => [
								['name' => 'key3']
							]
						]
					]
				]
			]
		], [
			123451 => $index1,
			123452 => $index2
		]);
		
		$this->assertEquals($index1, $studyStore->getQuestionnaireIndex($this->studyId, 123451));
		$this->assertEquals($index2, $studyStore->getQuestionnaireIndex($this->studyId, 123452));
	}
	
	function test_questionnaireExists() {
		$this->createStudy((object) [
			'id' => $this->studyId,
			'questionnaires' => [
				(object)[
					'internalId' => 123451
				],
				(object)[
					'internalId' => 123452
				]
			]
		], [
			123451 =>  new ResponsesIndex(['key3']),
			123452 =>  new ResponsesIndex(['key3'])
		]);
		$studyStore = Configs::getDataStore()->getStudyStore();
		$this->assertTrue($studyStore->questionnaireExists($this->studyId, 123451));
		$this->assertTrue($studyStore->questionnaireExists($this->studyId, 123452));
		$this->assertFalse($studyStore->questionnaireExists($this->studyId, 123453));
	}
	
	function test_createStudy() {
		$questionnaireId = 11111;
		$questionnaireKeys = ['key1', 'key2', 'key3'];
		$config =  (object)[
			'id' => $this->studyId,
			'questionnaires' => [
				(object) ['internalId' => $questionnaireId]
			]
		];
		$this->createStudy($config, [$questionnaireId => new ResponsesIndex($questionnaireKeys)]);
		
		$studyStore = Configs::getDataStore()->getStudyStore();
		$this->assertEquals(
			array_merge(KEYS_QUESTIONNAIRE_BASE_RESPONSES, $questionnaireKeys),
			$studyStore->getQuestionnaireIndex($this->studyId, $questionnaireId)->keys
		);
		$this->assertEquals(KEYS_EVENT_RESPONSES, $studyStore->getEventIndex($this->studyId)->keys);
		$this->assertEquals($config, $studyStore->getStudyConfig($this->studyId));
	}
	
	function test_createStudy_with_randomGroups() {
		$questionnaireId = 11111;
		$questionnaireKeys = ['key1', 'key2', 'key3'];
		$config =  (object)[
			'id' => $this->studyId,
			'randomGroups' => 1,
			'questionnaires' => [
				(object) ['internalId' => $questionnaireId]
			]
		];
		$this->createStudy($config, [$questionnaireId => new ResponsesIndex($questionnaireKeys)]);
		
		$studyStore = Configs::getDataStore()->getStudyStore();
		$this->assertEquals(
			array_merge(['group'], KEYS_QUESTIONNAIRE_BASE_RESPONSES, $questionnaireKeys),
			$studyStore->getQuestionnaireIndex($this->studyId, $questionnaireId)->keys
		);
	}
	
	function test_createStudy_with_updated_responses() {
		$questionnaireId = 11111;
		$config =  (object)[
			'id' => $this->studyId,
			'questionnaires' => [
				(object) ['internalId' => $questionnaireId]
			]
		];
		$studyStore = Configs::getDataStore()->getStudyStore();
		
		$this->createStudy($config, [$questionnaireId => new ResponsesIndex(['key1', 'key2'])]);
		$this->assertEquals(
			array_merge(KEYS_QUESTIONNAIRE_BASE_RESPONSES, ['key1', 'key2']),
			$studyStore->getQuestionnaireIndex($this->studyId, $questionnaireId)->keys
		);
		
		$this->createStudy($config, [$questionnaireId => new ResponsesIndex(['key1', 'key2', 'key3'])]);
		$this->assertEquals(
			array_merge(KEYS_QUESTIONNAIRE_BASE_RESPONSES, ['key1', 'key2', 'key3']),
			$studyStore->getQuestionnaireIndex($this->studyId, $questionnaireId)->keys
		);
	}
	
	function test_createStudy_with_existing_data_and_updated_responses() {
		$questionnaireId = 11111;
		$config =  (object)[
			'id' => $this->studyId,
			'questionnaires' => [
				(object) ['internalId' => $questionnaireId]
			]
		];
		$studyStore = Configs::getDataStore()->getStudyStore();
		
		$this->createStudy($config, [$questionnaireId => new ResponsesIndex(['key1', 'key2'])]);
		$dataset = new CreateDataSet();
		$dataset->prepare((object) [
			'userId' => 'userId',
			'appType' => 'appType',
			'appVersion' => 'appVersion',
			'serverVersion' => (string) Main::ACCEPTED_SERVER_VERSION,
			'dataset' => [
				(object) [
					'dataSetId' => (string) 123,
					'studyId' => (string) $this->studyId,
					'questionnaireInternalId' => (string) $questionnaireId,
					'eventType' => 'questionnaire',
					'responses' => (object) [
						'key1' => 'answer1',
						'key2' => 'answer2'
					]
				]
			]
		]);
		$dataset->exec();
		
		$this->createStudy($config, [$questionnaireId => new ResponsesIndex(['key1', 'key2', 'key3'])]);
		$this->assertEquals(
			array_merge(KEYS_QUESTIONNAIRE_BASE_RESPONSES, ['key1', 'key2', 'key3']),
			$studyStore->getQuestionnaireIndex($this->studyId, $questionnaireId)->keys
		);
	}
	
	function test_createStudy_with_updated_responses_where_filesize_is_too_big() {
		$questionnaireId = 11111;
		$config =  (object)[
			'id' => $this->studyId,
			'questionnaires' => [
				(object) ['internalId' => $questionnaireId]
			]
		];
		$path = PathsFS::fileResponsesBackup($this->studyId, (string) $questionnaireId);
		$this->createStudy($config, [$questionnaireId => new ResponsesIndex(['key1', 'key2'])]);
		
		Configs::injectConfig('configs.max_filesize.injected.php');
		
		$this->assertFileDoesNotExist($path);
		$this->createStudy($config, [$questionnaireId => new ResponsesIndex(['key1', 'key2', 'key3'])]);
		$this->assertFileExists($path);
	}
	
	function test_backupStudy() {
		$studyStore = Configs::getDataStore()->getStudyStore();
		
		$questionnaireId = 11111;
		$questionnaireKeys = ['key1', 'key2', 'key3'];
		$config =  (object)[
			'id' => $this->studyId,
			'questionnaires' => [
				(object) ['internalId' => $questionnaireId]
			]
		];
		$this->createStudy($config, [$questionnaireId => new ResponsesIndex($questionnaireKeys)]);
		
		//path variables have to be created before backupStudy() or the they will account for the already existing backup files:
		$pathQuestionnaire = PathsFS::fileResponses($this->studyId, (string) $questionnaireId);
		$pathQuestionnaireBackup = PathsFS::fileResponsesBackup($this->studyId, (string) $questionnaireId);
		$pathEvents = PathsFS::fileResponses($this->studyId, PathsFS::FILENAME_EVENTS);
		$pathEventsBackup = PathsFS::fileResponsesBackup($this->studyId, PathsFS::FILENAME_EVENTS);
		$pathWebAccess = PathsFS::fileResponses($this->studyId, PathsFS::FILENAME_WEB_ACCESS);
		$pathWebAccessBackup = PathsFS::fileResponsesBackup($this->studyId, PathsFS::FILENAME_WEB_ACCESS);
		
		$studyStore->backupStudy($this->studyId);
		
		$this->assertEquals(file_get_contents($pathQuestionnaire), file_get_contents($pathQuestionnaireBackup));
		$this->assertEquals(file_get_contents($pathEvents), file_get_contents($pathEventsBackup));
		$this->assertEquals(file_get_contents($pathWebAccess), file_get_contents($pathWebAccessBackup));
	}
	
	function test_emptyStudy() {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$responsesStore = Configs::getDataStore()->getResponsesStore();
		
		$csvDelimiter = Configs::get('csv_delimiter');
		$questionnaireId = 11111;
		$questionnaireKeys = ['key1', 'key2', 'key3'];
		$config =  (object)[
			'id' => $this->studyId,
			'questionnaires' => [
				(object) ['internalId' => $questionnaireId]
			]
		];
		$this->createStudy($config, [$questionnaireId => new ResponsesIndex($questionnaireKeys)]);
		
		
		//assign values:
		
		$pathZip = Paths::fileMediaZip($this->studyId);
		$pathImage = Paths::fileImageFromData($this->studyId, 'userId', 1, 'key');
		$pathStatisticsNewData = PathsFS::fileStatisticsNewData($this->studyId);
		$pathStatisticsJson = PathsFS::fileStatisticsJson($this->studyId);
		
		$expectedWebAccess = Main::arrayToCSV(KEYS_WEB_ACCESS, $csvDelimiter);
		$expectedQuestionnaire = Main::arrayToCSV(array_merge(KEYS_QUESTIONNAIRE_BASE_RESPONSES, $questionnaireKeys), $csvDelimiter);
		$expectedEvents = Main::arrayToCSV(KEYS_EVENT_RESPONSES, $csvDelimiter);
		$expectedResponseFileCount = 1;
		
		
		//fill study:
		
		file_put_contents($pathZip, 'zip content');
		file_put_contents($pathImage, 'image content');
		file_put_contents($pathStatisticsJson, '{"key": "value"}');
		$responsesStore->saveWebAccessDataSet($this->studyId, 0, 'pageName', 'referer', 'userAgent');
		$cache = new DataSetCache();
		$cache->addToEventCache($this->studyId, 111, []);
		$cache->addToQuestionnaireCache($this->studyId, $questionnaireId, 111, []);
		$cache->addToStatisticsCache($this->studyId, 1111, new DataSetCacheStatisticsEntry('key', 0, 0, 'answer')); // creates $pathStatisticsNewData
		$responsesStore->saveDataSetCache('userId', $cache, function() {}, function() {});
		$studyStore->backupStudy($this->studyId);
		
		
		//check that filling was successful:
		
		$this->assertFileExists($pathImage);
		$this->assertFileExists($pathZip);
		$this->assertFileExists($pathStatisticsJson);
		$this->assertFileExists($pathStatisticsNewData);
		
		$this->assertNotEquals(
			$expectedWebAccess,
			file_get_contents(PathsFS::fileResponses($this->studyId, PathsFS::FILENAME_WEB_ACCESS))
		);
		$this->assertNotEquals(
			$expectedQuestionnaire,
			file_get_contents(PathsFS::fileResponses($this->studyId, (string) $questionnaireId))
		);
		$this->assertNotEquals(
			$expectedEvents,
			file_get_contents(PathsFS::fileResponses($this->studyId, PathsFS::FILENAME_EVENTS))
		);
		
		$this->assertNotCount($expectedResponseFileCount, $responsesStore->getResponseFilesList($this->studyId)); //check for backups
		
		
		//empty:
		
		$studyStore->emptyStudy($this->studyId, [$questionnaireId => new ResponsesIndex($questionnaireKeys)]);
		
		
		//check if emptied:
		
		$this->assertFileDoesNotExist($pathImage);
		$this->assertFileDoesNotExist($pathZip);
		$this->assertFileDoesNotExist($pathStatisticsJson);
		$this->assertFileDoesNotExist($pathStatisticsNewData);
		
		$this->assertEquals(
			$expectedWebAccess,
			file_get_contents(PathsFS::fileResponses($this->studyId, PathsFS::FILENAME_WEB_ACCESS))
		);
		$this->assertEquals(
			$expectedQuestionnaire,
			file_get_contents(PathsFS::fileResponses($this->studyId, (string) $questionnaireId))
		);
		$this->assertEquals(
			$expectedEvents,
			file_get_contents(PathsFS::fileResponses($this->studyId, PathsFS::FILENAME_EVENTS))
		);
		
		$this->assertCount($expectedResponseFileCount, $responsesStore->getResponseFilesList($this->studyId));
	}
	
	function test_markStudyAsUpdated() {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$this->createEmptyStudy($this->studyId);
		$this->createStudy((object) [
			'id' => $this->studyId,
			'version' => 3,
			'subVersion' => 5,
			'new_changes' => false
		]);
		
		$studyStore->markStudyAsUpdated($this->studyId);
		$newConfig = $studyStore->getStudyConfig($this->studyId);
		
		$this->assertEquals(4, $newConfig->version);
		$this->assertEquals(0, $newConfig->subVersion);
		$this->assertFalse($newConfig->new_changes);
	}
	
	function test_save_and_delete_study() {
		$accessKey = 'key1';
		$store = new StudyStoreFS();
		$path = PathsFS::folderStudy($this->studyId);
		
		$this->assertFileDoesNotExist($path);
		
		//create study:
		$this->createEmptyStudy($this->studyId);
		$this->assertFileExists($path);
		
		//add to index:
		$accessKeyStore = new StudyAccessIndexStoreFS();
		$accessKeyStore->add($this->studyId, $accessKey);
		$accessKeyStore->saveChanges();
		$this->assertArrayHasKey($accessKey, StudyAccessKeyIndexLoader::importFile());
		
		
		$store->delete($this->studyId);
		$this->assertFileDoesNotExist($path);
	}
	
	function test_delete_non_existing_study() {
		$store = new StudyStoreFS();
		
		$this->expectException(CriticalException::class);
		$store->delete($this->studyId);
	}
	
	function test_delete_study_that_has_permissions() {
		$this->createEmptyStudy($this->studyId);
		
		$this->login();
		$this->addPermission('write', $this->studyId);
		$this->addPermission('read', $this->studyId);
		$this->addPermission('msg', $this->studyId);
		$this->addPermission('publish', $this->studyId);
		
		$this->assertTrue(Permission::hasPermission($this->studyId, 'write'));
		
		$store = new StudyStoreFS();
		$store->delete($this->studyId);
		
		
		$this->assertFalse(Permission::hasPermission($this->studyId, 'write'));
	}
}