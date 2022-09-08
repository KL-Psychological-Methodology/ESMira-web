<?php
declare(strict_types=1);

namespace test\backend;

require_once __DIR__ .'/../../backend/autoload.php';

use backend\Configs;
use backend\DataSetCache;
use backend\DataSetCacheStatisticsEntry;
use backend\DataStoreInterface;
use backend\ResponsesIndex;
use backend\subStores\StudyStore;
use test\testConfigs\BaseTestSetup;

class DataSetCacheTest extends BaseTestSetup {
	protected function tearDown(): void {
		Configs::resetAll();
	}
	
	function testFileCache() {
		$cache = new DataSetCache();
		$cache->addToFileCache(11, 'path/to/file1', 1231, 1);
		$cache->addToFileCache(12, 'path/to/file2', 1232, 2);
		$cache->addToFileCache(13, 'path/to/file3', 1233, 3);
		
		$fileCache = $cache->getFileCache();
		$this->assertCount(3, $fileCache);
		
		$this->assertEquals(11, $fileCache[1]->studyId);
		$this->assertEquals('path/to/file1', $fileCache[1]->internalPath);
		$this->assertEquals(1231, $fileCache[1]->identifier);
		
		$this->assertEquals(12, $fileCache[2]->studyId);
		$this->assertEquals('path/to/file2', $fileCache[2]->internalPath);
		$this->assertEquals(1232, $fileCache[2]->identifier);
		
		$this->assertEquals(13, $fileCache[3]->studyId);
		$this->assertEquals('path/to/file3', $fileCache[3]->internalPath);
		$this->assertEquals(1233, $fileCache[3]->identifier);
	}
	
	private function fillDataSetCache(array $sourceData, callable $add): DataSetCache {
		$cache = new DataSetCache();
		foreach($sourceData as $studyId => $studyEntry) {
			foreach($studyEntry as $dataSetId => $entry) {
				$add($cache, $studyId, $dataSetId, $entry);
			}
		}
		return $cache;
	}
	private function assertOutput(array $sourceData, array $output) {
		foreach($sourceData as $id => $sourceEntries) {
			$outputEntry = $output[$id];
			self::assertSameSize($sourceEntries, $outputEntry->ids);
			self::assertSameSize($sourceEntries, $outputEntry->data);
			$i = 0;
			foreach($sourceEntries as $dataSetId => $entry) {
				self::assertEquals($dataSetId, $outputEntry->ids[$i]);
				self::assertEquals($entry, $outputEntry->data[$i]);
				++$i;
			}
		}
	}
	
	function testStatisticsCache() {
		$sourceData = [
			11 => [
				123451 => new DataSetCacheStatisticsEntry('key', 1, 0, 'answer1'),
				123452 => new DataSetCacheStatisticsEntry('key', 1, 0, 'answer2'),
				123453 => new DataSetCacheStatisticsEntry('key', 1, 0, 'answer3')
			],
			12 => [
				123454 => new DataSetCacheStatisticsEntry('key', 1, 0, 'answer4'),
				123455 => new DataSetCacheStatisticsEntry('key', 1, 0, 'answer5')
			]
		];
		
		$cache = $this->fillDataSetCache($sourceData,
			function(DataSetCache $cache, int $studyId, int $dataSetId, DataSetCacheStatisticsEntry $entry) {
				$cache->addToStatisticsCache($studyId, $dataSetId, $entry);
			});
		
		$this->assertOutput($sourceData, $cache->getStatisticsCache());
	}
	function testEventCache() {
		$sourceData = [
			11 => [
				123451 => ['var1' => 'value1', 'var2' => 'value2', 'var3' => 'value3'],
				123452 => ['var4' => 'value4', 'var5' => 'value5', 'var6' => 'value6']
			],
			12 => [
				123453 => ['var7' => 'value7', 'var8' => 'value8', 'var9' => 'value9'],
				123454 => ['var7' => 'value7', 'var8' => 'value8', 'var9' => 'value9']
			]
		];
		
		$cache = $this->fillDataSetCache($sourceData,
			function(DataSetCache $cache, int $studyId, int $dataSetId, array $entry) {
				$cache->addToEventCache($studyId, $dataSetId, $entry);
			});
		
		$this->assertOutput($sourceData, $cache->getEventCache());
	}
	function testQuestionnaireCache() {
		$sourceData = [
			11 => [
				1231 => [
					123451 => ['var1' => 'value1', 'var2' => 'value2', 'var3' => 'value3'],
					123452 => ['var4' => 'value4', 'var5' => 'value5', 'var6' => 'value6']
				]
			],
			12 => [
				1232 => [
					123453 => ['var7' => 'value7', 'var8' => 'value8', 'var9' => 'value9'],
				],
				1233 => [
					123454 => ['var7' => 'value7', 'var8' => 'value8', 'var9' => 'value9']
				]
			]
		];
		
		$cache = $this->fillDataSetCache($sourceData,
			function(DataSetCache $cache, int $studyId, int $questionnaireId, array $questionnaireEntry) {
				foreach($questionnaireEntry as $dataSetId => $entry) {
					$cache->addToQuestionnaireCache($studyId, $questionnaireId, $dataSetId, $entry);
				}
			});
		
		$output = $cache->getQuestionnaireCache();
		
		
		foreach($sourceData as $studyId => $studyArray) {
			$outputStudyArray = $output[$studyId];
			self::assertSameSize($outputStudyArray, $studyArray);
			
			
			$this->assertOutput($studyArray, $outputStudyArray);
		}
	}
	
	function test_if_eventIndex_uses_correct_arguments() {
		$index1 = new ResponsesIndex([1]);
		$index2 = new ResponsesIndex([2]);
		$cache = new DataSetCache();
		
		//test if arguments are used correctly:
		$studyStoreStub = $this->createStub(StudyStore::class);
		$studyStoreStub->method('getEventIndex')->will($this->returnValueMap([
			[121, $index1],
			[122, $index2]
		]));
		
		$dataStoreStub = $this->createStub(DataStoreInterface::class);
		$dataStoreStub->method('getStudyStore')
			->willReturnCallback(function() use($studyStoreStub): StudyStore {
				return $studyStoreStub;
			});
		Configs::injectDataStore($dataStoreStub);
		
		$this->assertEquals($index1, $cache->getEventIndex(121));
		$this->assertEquals($index2, $cache->getEventIndex(122));
	}
	function test_if_eventIndex_is_cached() {
		$index1 = new ResponsesIndex([1]);
		$index2 = new ResponsesIndex([2]);
		$index3 = new ResponsesIndex([3]);
		
		$cache = new DataSetCache();
		
		$studyStoreStub = $this->createStub(StudyStore::class);
		$studyStoreStub->method('getEventIndex')->will($this->onConsecutiveCalls($index1, $index2, $index3));
		
		$dataStoreStub = $this->createStub(DataStoreInterface::class);
		$dataStoreStub->method('getStudyStore')->willReturnCallback(function() use($studyStoreStub): StudyStore {
			return $studyStoreStub;
		});
		Configs::injectDataStore($dataStoreStub);
		
		$this->assertEquals($index1, $cache->getEventIndex(121));
		$this->assertEquals($index2, $cache->getEventIndex(122));
		$this->assertEquals($index1, $cache->getEventIndex(121));
	}
	function test_if_questionnaireIndex_uses_correct_arguments() {
		$index1 = new ResponsesIndex([1]);
		$index2 = new ResponsesIndex([2]);
		
		
		$studyStoreStub = $this->createStub(StudyStore::class);
		$studyStoreStub
			->method('getQuestionnaireIndex')
			->will($this->returnValueMap([
				[121, 12341, $index1],
				[122, 12342, $index2]
			]));
		
		$dataStoreStub = $this->createStub(DataStoreInterface::class);
		$dataStoreStub
			->method('getStudyStore')
			->willReturnCallback(function() use($studyStoreStub): StudyStore {
				return $studyStoreStub;
			});
		Configs::injectDataStore($dataStoreStub);
		
		$cache = new DataSetCache();
		$this->assertEquals($index1, $cache->getQuestionnaireIndex(121, 12341));
		$this->assertEquals($index2, $cache->getQuestionnaireIndex(122, 12342));
	}
	function test_if_questionnaireIndex_is_cached() {
		$index1 = new ResponsesIndex([1]);
		$index2 = new ResponsesIndex([2]);
		$index3 = new ResponsesIndex([3]);
		
		$cache = new DataSetCache();
		
		$studyStoreStub = $this->createStub(StudyStore::class);
		$studyStoreStub
			->method('getQuestionnaireIndex')
			->will($this->onConsecutiveCalls($index1, $index2, $index3));
		
		$dataStoreStub = $this->createStub(DataStoreInterface::class);
		$dataStoreStub
			->method('getStudyStore')
			->willReturnCallback(function() use($studyStoreStub) {
				return $studyStoreStub;
			});
		
		
		
		Configs::injectDataStore($dataStoreStub);
		
		$this->assertEquals($index1, $cache->getQuestionnaireIndex(121, 12341));
		$this->assertEquals($index2, $cache->getQuestionnaireIndex(122, 12342));
		$this->assertEquals($index3, $cache->getQuestionnaireIndex(122, 12343));
		$this->assertEquals($index1, $cache->getQuestionnaireIndex(121, 12341));
	}
}