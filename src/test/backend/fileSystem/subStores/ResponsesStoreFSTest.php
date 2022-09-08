<?php

namespace test\backend\fileSystem\subStores;

use backend\Configs;
use backend\DataSetCache;
use backend\DataSetCacheStatisticsEntry;
use backend\fileSystem\loader\StatisticsNewDataSetEntryLoader;
use backend\fileSystem\PathsFS;
use backend\FileUploader;
use backend\Main;
use backend\Paths;
use backend\ResponsesIndex;
use PHPUnit\Framework\ExpectationFailedException;
use test\testConfigs\BaseDataFolderTestSetup;
use ZipArchive;

require_once __DIR__ .'/../../../../backend/autoload.php';

class ResponsesStoreFSTest extends BaseDataFolderTestSetup {
	private $userId = 'userId';
	protected function tearDown(): void {
		self::tearDownAfterClass();
	}
	protected function setUp(): void {
		self::setUpBeforeClass();
	}
	
	
	private function saveDataSetCache(DataSetCache $cache, callable $errorCallback = null) {
		Configs::getDataStore()->getResponsesStore()->saveDataSetCache($this->userId, $cache, function() {},
			$errorCallback ?? function(int $id, string $msg) {
			throw new ExpectationFailedException("Error was called for id $id\n$msg");
		});
	}
	
	function test_saveWebAccessDataSet() {
		$studyId = 123;
		$responseTime = time();
		$pageName = 'name';
		$referer = 'referer';
		$userAgent = 'userAgent';
		
		$this->createStudy((object) ['id' => $studyId]);
		Configs::getDataStore()->getResponsesStore()->saveWebAccessDataSet(
			$studyId,
			$responseTime,
			$pageName,
			$referer,
			$userAgent
		);
		
		$path = PathsFS::fileResponses($studyId, PathsFS::FILENAME_WEB_ACCESS);
		$this->assertEquals(
			Main::arrayToCSV(KEYS_WEB_ACCESS, Configs::get('csv_delimiter'))
			."\n" .Main::arrayToCSV([$responseTime, $pageName, $referer, $userAgent], Configs::get('csv_delimiter')),
			file_get_contents($path)
		);
	}
	
	function test_saveDataSetCache_with_statistics() {
		$studyId = 123;
		$source = [
			new DataSetCacheStatisticsEntry('key', 1, 0, 'answer1'),
			new DataSetCacheStatisticsEntry('key', 1, 0, 'answer2'),
			new DataSetCacheStatisticsEntry('key', 1, 0, 'answer3')
		];
		
		$this->createEmptyStudy($studyId);
		$cache = new DataSetCache();
		
		$expectedContent = '';
		foreach($source as $entry) {
			$cache->addToStatisticsCache($studyId, 1111, $entry);
			$expectedContent .= "\n" .StatisticsNewDataSetEntryLoader::export($entry);
		}
		
		$this->saveDataSetCache($cache);
		$this->assertEquals($expectedContent, file_get_contents(PathsFS::fileStatisticsNewData($studyId)));
	}
	function test_saveDataSetCache_with_questionnaires() {
		$studyId = 123;
		$source = [
			['id'=> 123451, 'data' => ['key1' => 'var1']],
			['id'=> 123451, 'data' => ['key2' => 'var2']],
			['id'=> 123452, 'data' => ['key3' => 'var3']]
		];
		
		$this->createStudy((object) [
			'id' => $studyId,
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
			123451 => new ResponsesIndex(['key1', 'key2']),
			123452 => new ResponsesIndex(['key3'])
		]);
		$cache = new DataSetCache();
		
		$expectedContent = [
			123451 => file_get_contents(PathsFS::fileResponses($studyId, 123451)),
			123452 => file_get_contents(PathsFS::fileResponses($studyId, 123452))
		];
		$csvDelimiter = Configs::get('csv_delimiter');
		foreach($source as $entry) {
			$cache->addToQuestionnaireCache($studyId, $entry['id'], 1111, $entry['data']);
			
			$expectedContent[$entry['id']] .= "\n" .Main::arrayToCSV($entry['data'], $csvDelimiter);
		}
		
		$this->saveDataSetCache($cache);
		$this->assertEquals($expectedContent[123451], file_get_contents(PathsFS::fileResponses($studyId, 123451)));
		$this->assertEquals($expectedContent[123452], file_get_contents(PathsFS::fileResponses($studyId, 123452)));
	}
	function test_saveDataSetCache_with_events() {
		$studyId = 123;
		$source = [
			['data' => ['key1' => 'var1']],
			['data' => ['key2' => 'var2']],
			['data' => ['key3' => 'var3']]
		];
		
		$this->createEmptyStudy($studyId);
		$cache = new DataSetCache();
		
		$expectedContent = file_get_contents(PathsFS::fileResponses($studyId, PathsFS::FILENAME_EVENTS));
		$csvDelimiter = Configs::get('csv_delimiter');
		foreach($source as $entry) {
			$cache->addToEventCache($studyId, 111, $entry['data']);
			$expectedContent .= "\n" .Main::arrayToCSV($entry['data'], $csvDelimiter);
		}
		
		$this->saveDataSetCache($cache);
		$this->assertEquals($expectedContent, file_get_contents(PathsFS::fileResponses($studyId, PathsFS::FILENAME_EVENTS)));
	}
	function test_saveDataSetCache_with_files() {
		$studyId = 123;
		$userId = 'test';
		$source = [
			['path' => 'path/to/file1', 'identifier' => 111, 'datasetId' => 1111],
			['path' => 'path/to/file2', 'identifier' => 112, 'datasetId' => 1112],
			['path' => 'path/to/file3', 'identifier' => 113, 'datasetId' => 1113]
		];
		
		$this->createEmptyStudy($studyId);
		$cache = new DataSetCache();
		
		foreach($source as $entry) {
			$cache->addToFileCache($studyId, $entry['path'], $entry['identifier'], $entry['datasetId']);
		}
		
		$this->saveDataSetCache($cache);
		foreach($source as $entry) {
			$file = PathsFS::filePendingUploads($studyId, $this->userId, $entry['identifier']);
			$this->assertFileExists($file);
			$this->assertEquals($entry['path'], file_get_contents($file));
		}
	}
	function test_saveDataSetCache_with_error() {
		$studyId = 123;
		$source = [ //we are creating an empty study, so all these datasets should fail
			['qId'=> 123451, 'datasetId' => 1231, 'data' => ['key1' => 'var1']],
			['qId'=> 123451, 'datasetId' => 1232, 'data' => ['key2' => 'var2']],
			['qId'=> 123452, 'datasetId' => 1233, 'data' => ['key3' => 'var3']]
		];
		
		$this->createEmptyStudy($studyId);
		$cache = new DataSetCache();
		
		foreach($source as $entry) {
			$cache->addToQuestionnaireCache($studyId, $entry['qId'], $entry['datasetId'], $entry['data']);
		}
		
		$errors = [];
		
		$this->saveDataSetCache($cache, function($id) use (&$errors) {
			$errors[] = $id;
		});
		$this->assertEquals([1231, 1232, 1233], $errors);
	}
	
	function test_uploadFile() {
		$studyId = 123;
		$targetPath = 'some/path';
		$zipPath = Paths::fileMediaZip($studyId);
		$identifier = 10000;
		
		$this->createEmptyStudy($studyId);
		file_put_contents($zipPath, 'zip content');
		$cache = new DataSetCache();
		$cache->addToFileCache($studyId, $targetPath, $identifier, 987);
		$this->saveDataSetCache($cache);
		
		$fileUploader = $this->createMock(FileUploader::class);
		$fileUploader->expects($this->once())
			->method('upload')
			->with($targetPath)
			->willReturn(true);
		
		Configs::getDataStore()->getResponsesStore()->uploadFile($studyId, $this->userId, $identifier, $fileUploader);
		
		$this->assertFileDoesNotExist($zipPath);
	}
	function test_uploadFile_with_unexpected_upload() {
		$studyId = 123;
		$identifier = 10000;
		
		
		$this->createEmptyStudy($studyId);
		$cache = new DataSetCache();
		$this->saveDataSetCache($cache);
		
		$fileUploader = $this->createMock(FileUploader::class);
		
		$this->expectErrorMessage('Not allowed');
		Configs::getDataStore()->getResponsesStore()->uploadFile($studyId, $this->userId, $identifier, $fileUploader);
	}
	function test_uploadFile_with_already_existing_upload() {
		$studyId = 123;
		$targetPath = TEST_DATA_FOLDER .'uploadFile';
		$identifier = 10000;
		
		file_put_contents($targetPath, 'content');
		
		$this->createEmptyStudy($studyId);
		$cache = new DataSetCache();
		$cache->addToFileCache($studyId, $targetPath, $identifier, 987);
		$this->saveDataSetCache($cache);
		
		$fileUploader = $this->createMock(FileUploader::class);
		
		$this->expectErrorMessage('File already exists');
		Configs::getDataStore()->getResponsesStore()->uploadFile($studyId, $this->userId, $identifier, $fileUploader);
	}
	function test_uploadFile_when_upload_fails() {
		$studyId = 123;
		$targetPath = 'some/path';
		$identifier = 10000;
		
		$this->createEmptyStudy($studyId);
		$cache = new DataSetCache();
		$cache->addToFileCache($studyId, $targetPath, $identifier, 987);
		$this->saveDataSetCache($cache);
		
		$fileUploader = $this->createMock(FileUploader::class);
		$fileUploader->expects($this->once())
			->method('upload')
			->with($targetPath)
			->willReturn(false);
		
		$this->expectErrorMessage('Uploading failed');
		Configs::getDataStore()->getResponsesStore()->uploadFile($studyId, $this->userId, $identifier, $fileUploader);
		
	}
	
	function test_getLastResponseTimestampOfStudies() {
		$studyId1 = 123;
		$studyId2 = 456;
		$studyId3 = 789;
		$this->createEmptyStudy($studyId1);
		$this->createEmptyStudy($studyId2);
		$this->createEmptyStudy($studyId3);
		
		$timeStudy1 = filemtime(PathsFS::fileResponses($studyId1, PathsFS::FILENAME_EVENTS));
		$timeStudy2 = filemtime(PathsFS::fileResponses($studyId2, PathsFS::FILENAME_EVENTS));
		$timeStudy3 = filemtime(PathsFS::fileResponses($studyId3, PathsFS::FILENAME_EVENTS));
		
		$time = time();
		$responsesStore = Configs::getDataStore()->getResponsesStore();
		
		$this->assertEquals(
			[$studyId1 => $timeStudy1, $studyId2 => $timeStudy2, $studyId3 => $timeStudy3],
			$responsesStore->getLastResponseTimestampOfStudies()
		);
		
		sleep(1);
		
		$cache = new DataSetCache();
		$cache->addToEventCache($studyId1, 111, []);
		$this->saveDataSetCache($cache);
		
		$timeStudy1 = filemtime(PathsFS::fileResponses($studyId1, PathsFS::FILENAME_EVENTS));
		
		$responsesStore = Configs::getDataStore()->getResponsesStore();
		$this->assertEquals(
			[$studyId1 => $timeStudy1, $studyId2 => $timeStudy2, $studyId3 => $timeStudy3],
			$responsesStore->getLastResponseTimestampOfStudies()
		);
	}
	
	function test_createMediaZip() {
		$studyId = 123;
		$responsesStore = Configs::getDataStore()->getResponsesStore();
		
		$this->createEmptyStudy($studyId);
		
		
		$pathZip = Paths::fileMediaZip($studyId);
		$this->assertFalse(file_exists($pathZip));
		
		$pathImages = Paths::fileImageFromData($studyId, 'user', 111, 'key');
		file_put_contents($pathImages, 'image');
		
		$responsesStore->createMediaZip($studyId);
		
		$this->assertTrue(file_exists($pathZip));
		
		$zip = new ZipArchive();
		$zip->open($pathZip);
		$this->assertEquals(1, $zip->numFiles);
		
		$zip->close();
	}
	
	function test_outputResponsesFile() {
		$studyId = 123;
		$responsesStore = Configs::getDataStore()->getResponsesStore();
		$this->createEmptyStudy($studyId);
		
		$this->expectOutputString(Main::arrayToCSV(KEYS_EVENT_RESPONSES, Configs::get('csv_delimiter')));
		$responsesStore->outputResponsesFile($studyId, PathsFS::FILENAME_EVENTS);
	}
	
	function test_outputImageFromResponses() {
		$studyId = 123;
		$content = 'imageContent';
		$responsesStore = Configs::getDataStore()->getResponsesStore();
		$this->createEmptyStudy($studyId);
		
		$pathImages = Paths::fileImageFromData($studyId, 'userId', 111, 'key');
		file_put_contents($pathImages, $content);
		
		$this->expectOutputString($content);
		$responsesStore->outputImageFromResponses($studyId, 'userId', 111, 'key');
	}
	
	function test_getResponseFilesList() {
		$studyId = 123;
		$studyId2 = 1234;
		$internalId = 1234567;
		$responsesStore = Configs::getDataStore()->getResponsesStore();
		$this->createEmptyStudy($studyId);
		$this->createStudy((object) [
			'id' => $studyId2,
			'questionnaires' => [
				(object) ['internalId' => $internalId]
			]
		], [
			$internalId => new ResponsesIndex(['key1', 'key2']),
		]);
		
		$this->assertEquals([], $responsesStore->getResponseFilesList($studyId));
		$this->assertEquals([$internalId], $responsesStore->getResponseFilesList($studyId2));
	}
}