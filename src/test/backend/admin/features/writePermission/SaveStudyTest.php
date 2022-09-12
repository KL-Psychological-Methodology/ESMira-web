<?php

namespace test\backend\admin\features\writePermission;

use backend\admin\features\writePermission\SaveStudy;
use backend\Configs;
use backend\CreateDataSet;
use backend\dataClasses\StatisticsJsonEntry;
use backend\dataClasses\StudyStatisticsMetadataEntry;
use backend\dataClasses\StudyStatisticsEntry;
use backend\Main;
use backend\ResponsesIndex;
use backend\subStores\ServerStatisticsStore;
use backend\subStores\StatisticsStoreWriter;
use backend\subStores\StudyAccessIndexStore;
use backend\subStores\StudyStatisticsMetadataStore;
use backend\subStores\StudyStatisticsStore;
use backend\subStores\StudyStore;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\Stub;
use stdClass;
use test\testConfigs\BaseWritePermissionTestSetup;
use test\testConfigs\SkipArgument;
use function PHPUnit\Framework\assertEquals;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class SaveStudyTest extends BaseWritePermissionTestSetup {
	private $oneDay = 86400; //in seconds: 60*60*24
	private $smallestDistance;
	private $internalId = 2345;
	private $lastChanged = 999;
	/**
	 * @var StudyStatisticsStore
	 */
	private $studyStatisticsStore;
	/**
	 * @var array
	 */
	private $statisticsMetadata;
	
	private $statisticsStudyCountChange = 0; //should be -1, 0 or 1
	
	private $getStudyIdForQuestionnaireIdReturn = -1;
	private $removeStudyReturn = true;
	
	public function setUp(): void {
		$this->smallestDistance = Configs::get('smallest_timed_distance');
		$this->statisticsMetadata = [];
		$this->studyStatisticsStore = $this->createStub(StudyStatisticsStore::class);
		$this->getStudyIdForQuestionnaireIdReturn = $this->studyId;
		$this->setGet([
			'lastChanged' => $this->lastChanged
		]);
		parent::setUp();
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$observer->method('getStudyStatisticsStore')
			->willReturnCallback(function() {
				return $this->studyStatisticsStore;
			});
		
		
		$studyStatisticsMetadataStore = $this->createStub(StudyStatisticsMetadataStore::class);
		$studyStatisticsMetadataStore->method('loadMetadataCollection')
			->willReturnCallback(function(): array {
				return $this->statisticsMetadata;
			});
		$this->createStoreMock(
			'getStudyStatisticsMetadataStore',
			$studyStatisticsMetadataStore,
			$observer
		);
		
		
		$statisticsStore = $this->createStub(ServerStatisticsStore::class);
		$statisticsStore->method('update')
			->willReturnCallback(function(callable $callback) {
				$callback(new class($this->statisticsStudyCountChange) implements StatisticsStoreWriter {
					/**
					 * @var int
					 */
					private $statisticsStudyCountChange;
					public function __construct(int &$statisticsStudyCountChange) {
						$this->statisticsStudyCountChange = &$statisticsStudyCountChange;
					}
					
					public function incrementStudies() {
						++$this->statisticsStudyCountChange;
					}
					
					public function decrementStudies() {
						--$this->statisticsStudyCountChange;
					}
					
					public function incrementUser(int $num = 1) {
						throw new ExpectationFailedException('Not expected to be called');
					}
					
					public function incrementAndroid(int $num = 1) {
						throw new ExpectationFailedException('Not expected to be called');
					}
					
					public function incrementIos(int $num = 1) {
						throw new ExpectationFailedException('Not expected to be called');
					}
					
					public function incrementWeb(int $num = 1) {
						throw new ExpectationFailedException('Not expected to be called');
					}
					
					public function addDataToDay(int $oldestAllowedEntryTime, int $startOfDay, string $appType, string $appVersion, int $questionnaireEvents, int $joinEvents) {
						throw new ExpectationFailedException('Not expected to be called');
					}
				});
			});
		$this->createStoreMock(
			'getServerStatisticsStore',
			$statisticsStore,
			$observer
		);
		
		
		$studyStore = $this->createDataMock(
			StudyStore::class,
			'getStudyConfig',
			$this->removeExpected($this->createConfig())
		);
		$studyStore->method('getStudyLastChanged')
			->willReturn($this->lastChanged);
		$this->createStoreMock(
			'getStudyStore',
			$studyStore,
			$observer
		);
		$this->addDataMock($studyStore, 'saveStudy');
		
		
		$studyAccessStore = $this->createStub(StudyAccessIndexStore::class);
		$studyAccessStore->method('removeStudy')
			->willReturnCallback(function() {
				return $this->removeStudyReturn;
			});
		$studyAccessStore->method('getStudyIdForQuestionnaireId')
			->willReturnCallback(function() {
				return $this->getStudyIdForQuestionnaireIdReturn;
			});
		$this->addDataMock($studyAccessStore, 'add');
		$this->createStoreMock(
			'getStudyAccessIndexStore',
			$studyAccessStore,
			$observer
		);
		
		return $observer;
	}
	
	private function createStudyStatisticsMock(stdClass $initial, stdClass $expected): StudyStatisticsStore {
		return new class($initial, $expected) implements StudyStatisticsStore {
			/**
			 * @var stdClass
			 */
			private $initialJson;
			/**
			 * @var stdClass
			 */
			private $expectedJson;
			/**
			 * @var stdClass
			 */
			private $json;
			public function __construct(stdClass $initial, stdClass $expected) {
				$this->initialJson = $initial;
				$this->expectedJson = $expected;
				$this->json = new stdClass();
			}
			
			function addEntry(string $key, StatisticsJsonEntry $jsonEntry) {
				if(!isset($this->json->{$key}))
					$this->json->{$key} = [];
				
				$this->json->{$key}[] = $jsonEntry;
			}
			
			public function getStatistics(): stdClass {
				return $this->initialJson;
			}
			
			public function saveChanges() {
				assertEquals($this->expectedJson, $this->json);
			}
		};
	}
	
	
	private function createConfig($langCode = '_', bool $withExistingVersion = true, callable $adjustConfig = null): stdClass {
		$study =  (object) [
			'id' => $this->studyId,
			'accessKeys' => ['accessKey1', 'accessKey2'],
			'published' => false,
			'questionnaires' => [
				(object) [
					'internalId' => $this->internalId,
					'title' => "questionnaire$langCode",
					'pages' => [
						(object) [
							'inputs' => [
								(object) ['name' => 'input1', 'responseType' => 'photo'],
								(object) [],
								(object) ['name' => 'input2']
							],
							'sumScores' => [
								(object) [],
								(object) ['name' => 'sumScore1']
							]
						]
					]
				]
			],
			'publicStatistics' => (object) [
				'charts' => [
					(object) [
						'__expected' => ['storageType' => CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED],
						'dataType' => CreateDataSet::STATISTICS_DATATYPES_SUM,
						'axisContainer' => [
							(object) [
								'yAxis' => (object) [ //#1
									'__expected' => ['observedVariableIndex' => 0],
									'variableName' => 'input1',
								],
								'xAxis' => (object) [ //not needed
									'__expected' => ['observedVariableIndex' => -1],
									'variableName' => '',
								]
							],
							(object) [],
							(object) [
								'yAxis' => (object) [ //#2
									'__expected' => ['observedVariableIndex' => 1],
									'variableName' => 'input1',
									'conditionType' => CreateDataSet::CONDITION_TYPE_AND,
									'conditions' => [
										(object) [
											'key' => 'input1',
											'operator' => CreateDataSet::CONDITION_OPERATOR_EQUAL,
											'value' => 'value1'
										]
									]
								]
							]
						]
					]
				],
				'__expected' => [
					'observedVariables' => (object) [
						'input1' => [
							new StudyStatisticsEntry( //#1
								[],
								CreateDataSet::CONDITION_TYPE_ALL,
								CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
								$this->oneDay
							),
							new StudyStatisticsEntry( //#2
								[
									(object) [
										'key' => 'input1',
										'operator' => CreateDataSet::CONDITION_OPERATOR_EQUAL,
										'value' => 'value1'
									]
								],
								CreateDataSet::CONDITION_TYPE_AND,
								CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
								$this->oneDay
							),
							new StudyStatisticsEntry( //#5
								[],
								CreateDataSet::CONDITION_TYPE_ALL,
								CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
								$this->smallestDistance
							)
						],
						'sumScore1' => [
							new StudyStatisticsEntry( //#6
								[],
								CreateDataSet::CONDITION_TYPE_ALL,
								CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
								$this->smallestDistance
							)
						]
					]
				]
			],
			'personalStatistics' => (object) [
				'charts' => [
					(object) [
						'__expected' => ['storageType' => CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED],
						'dataType' => CreateDataSet::STATISTICS_DATATYPES_DAILY,
						'displayPublicVariable' => true,
						'publicVariables' => [
							(object) [
								'yAxis' => (object) [ //should get same observedVariableIndex as #1
									'__expected' => ['observedVariableIndex' => 0],
									'variableName' => 'input1',
								]
							],
						]
					],
					(object) [
						'__expected' => ['storageType' => CreateDataSet::STATISTICS_STORAGE_TYPE_FREQ_DISTR],
						'dataType' => CreateDataSet::STATISTICS_DATATYPES_FREQ_DISTR,
						'axisContainer' => [
							(object) [
								'yAxis' => (object) [ //#4
									'__expected' => ['observedVariableIndex' => 0],
									'variableName' => 'input1',
								]
							]
						],
					],
					(object) [
						'__expected' => ['storageType' => CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED],
						'dataType' => CreateDataSet::STATISTICS_DATATYPES_XY,
						'axisContainer' => [
							(object) [
								'yAxis' => (object) [ //#7
									'__expected' => ['observedVariableIndex' => 0],
									'variableName' => 'sumScore1',
								],
								'xAxis' => (object) [ //#8
									'__expected' => ['observedVariableIndex' => 1],
									'variableName' => 'input1',
								]
							]
						],
						'displayPublicVariable' => true,
						'publicVariables' => [
							(object) [
								'yAxis' => (object) [ //#5
									'__expected' => ['observedVariableIndex' => 2],
									'variableName' => 'input1',
								],
								'xAxis' => (object) [ //#6
									'__expected' => ['observedVariableIndex' => 0],
									'variableName' => 'sumScore1',
								]
							]
						]
					]
				],
				'__expected' => [
					'observedVariables' => (object) [
						'input1' => [
							new StudyStatisticsEntry( //#4
								[],
								CreateDataSet::CONDITION_TYPE_ALL,
								CreateDataSet::STATISTICS_STORAGE_TYPE_FREQ_DISTR,
								0
							),
							new StudyStatisticsEntry( //#8
								[],
								CreateDataSet::CONDITION_TYPE_ALL,
								CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
								$this->smallestDistance
							)
						],
						'sumScore1' => [
							new StudyStatisticsEntry( //#7
								[],
								CreateDataSet::CONDITION_TYPE_ALL,
								CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
								$this->smallestDistance
							)
						]
					]
				]
			]
		];
		
		if($withExistingVersion) {
			$study->version = 3;
			$study->subVersion = 4;
		}
		if($adjustConfig)
			$adjustConfig($study);
		return $study;
	}
	private function createExpectedConfig(string $langCode = '_', bool $withExistingVersion = true, callable $adjustConfig = null): stdClass {
		$config = $this->createConfig($langCode, $withExistingVersion, $adjustConfig);
		
		//
		//automated changes:
		//
		$this->setExpected($config);
		
		
		
		if($withExistingVersion) {
			++$config->subVersion;
			$config->new_changes = true;
		}
		else {
			$config->version = 1;
			$config->subVersion = 0;
		}
		
		return $config;
	}
	private function createCollection(bool $asResult = false, bool $withExistingVersion = true, callable $adjustConfig = null): stdClass {
		$function = $asResult ? 'createExpectedConfig' : 'createConfig';
		$callback = function(stdClass $study) use($adjustConfig) {
			if($adjustConfig)
				$adjustConfig($study);
		};
		return (object) [
			'_' => $this->removeExpected($this->{$function}('_', $withExistingVersion, $callback)),
			'en' => $this->removeExpected($this->{$function}('en', $withExistingVersion, $callback))
		];
	}
	
	
	private function removeExpected(/*mixed*/ $parent) {
		foreach($parent as $child) {
			if(is_object($child) || is_array($child))
				$this->removeExpected($child);
			if(isset($child->__expected)) {
				unset($child->__expected);
			}
		}
		return $parent; //convenience return
	}
	private function setExpected(/*mixed*/ $parent) {
		foreach($parent as $child) {
			if((is_object($child) || is_array($child)))
				$this->setExpected($child);
			if(isset($child->__expected)) {
				foreach($child->__expected as $key => $value) {
					$child->{$key} = $value;
				}
			}
		}
	}
	
	private function assertEqualConfigs(stdClass $expected, stdClass $actual) {
		//we use json_encode() to make sure classes are translated properly
		//but we also want the error message to be readable so we then use json_decode()
		$this->assertEquals(json_decode(json_encode($expected)), json_decode(json_encode($actual)));
	}
	
	function test_with_new_study() {
		Main::$defaultPostInput = json_encode($this->createCollection(false, false));
		$obj = new SaveStudy();
		$output = $obj->exec();
		$this->assertArrayHasKey('lastChanged', $output);
		$this->assertEqualConfigs($this->createCollection(true, false), $output['json']);
	}
	function test_with_already_existing_study() {
		$initialStatisticsInformation = [
			'input1' => [
				[
					'observable' => new StudyStatisticsEntry( //#2
						[
							(object) [
								'key' => 'input1',
								'operator' => CreateDataSet::CONDITION_OPERATOR_EQUAL,
								'value' => 'value1'
							]
						],
						CreateDataSet::CONDITION_TYPE_AND,
						CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
						$this->oneDay
					),
					'initialValue' => 9
				],
				[
					'observable' =>
						new StudyStatisticsEntry( //#5
							[],
							CreateDataSet::CONDITION_TYPE_ALL,
							CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
							$this->smallestDistance
						),
					'initialValue' => 7
				]
			],
			'sumScore1' => [
				[
					'observable' =>
						new StudyStatisticsEntry( //#6
							[],
							CreateDataSet::CONDITION_TYPE_ALL,
							CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
							$this->smallestDistance
						),
					'initialValue' => 2
				]
			]
		];
		$initialStatistics = (object) [];
		$metadata = [];
		foreach($initialStatisticsInformation as $key => $entries) {
			$initialStatistics->{$key} = [];
			foreach($entries as $info) {
				$initialStatistics->{$key}[] = new StatisticsJsonEntry($info['observable'], $info['initialValue']);
				$metadata[$key][] = $info['observable'];
			}
		}
		$expectedStatistics = (object) [
			'input1' => [
				new StatisticsJsonEntry(new StudyStatisticsEntry( //#1
					[],
					CreateDataSet::CONDITION_TYPE_ALL,
					CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
					$this->oneDay
				)),
				new StatisticsJsonEntry(new StudyStatisticsEntry( //#2
					[],
					CreateDataSet::CONDITION_TYPE_ALL,
					CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
					$this->oneDay
				),
					9
				),
				new StatisticsJsonEntry(new StudyStatisticsEntry( //#5
					[],
					CreateDataSet::CONDITION_TYPE_ALL,
					CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
					$this->smallestDistance
				),
				7
				)
			],
			'sumScore1' => [
				new StatisticsJsonEntry(new StudyStatisticsEntry( //#6
					[],
					CreateDataSet::CONDITION_TYPE_ALL,
					CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
					$this->smallestDistance
				),
					2
				)
			]
		];
		$this->studyStatisticsStore = $this->createStudyStatisticsMock($initialStatistics, $expectedStatistics);
		$this->statisticsMetadata = $metadata;
		
		Main::$defaultPostInput = json_encode($this->createCollection());
		$obj = new SaveStudy();
		$output = $obj->exec();
		$this->assertArrayHasKey('lastChanged', $output);
		$this->assertEqualConfigs($this->createCollection(true), $output['json']);
	}
	function test_with_identical_internalIds() {
		$newInternalId = $this->internalId;
		$this->getStudyIdForQuestionnaireIdReturn = -1;
		$adjustConfig = function($study) use(&$newInternalId) {
			$study->questionnaires[] = (object) [
				'internalId' => $newInternalId,
				'title' => 'second',
				'actionTriggers' => [
					(object) [
						'eventTriggers' => [
							(object) [
								'specificQuestionnaireInternalId' => $newInternalId
							]
						]
					]
				]
			];
		};
		Main::$defaultPostInput = json_encode($this->createCollection(false, false, $adjustConfig));
		
		$obj = new SaveStudy();
		$output = $obj->exec();
		$newInternalId = $output['json']->_->questionnaires[1]->internalId;
		$this->assertEqualConfigs(
			$this->createCollection(true, false, $adjustConfig),
			$output['json']
		);
	}
	function test_with_questionnaires() {
		$this->getStudyIdForQuestionnaireIdReturn = $this->studyId;
		$adjustConfig = function($study) {
			$study->questionnaires = [(object) [
				'internalId' => $this->internalId,
				'title' => 'second',
				'pages' => [
					(object) [
						'inputs' => [
							(object) [
								'name' => 'key1',
								'responseType' => 'text_input'
							]
						]
					]
				]
			]];
		};
		Main::$defaultPostInput = json_encode($this->createCollection(false, false, $adjustConfig));
		
		$obj = new SaveStudy();
		$obj->exec();
		
		$responsesIndex = new ResponsesIndex();
		$responsesIndex->addInput('text_input', 'key1');
		$this->assertDataMock('saveStudy', [
			new SkipArgument(),
			[$this->internalId => $responsesIndex]
		]);
	}
	
	function test_with_empty_accessKey() {
		$this->publishPermissions = [$this->studyId];
		$adjustConfig = function($study) {
			$study->published = true;
			$study->accessKeys = [''];
		};
		Main::$defaultPostInput = json_encode($this->createCollection(false, false, $adjustConfig));
		
		$obj = new SaveStudy();
		$this->expectErrorMessage('empty');
		$obj->exec();
	}
	function test_with_accessKey_with_special_characters() {
		$this->publishPermissions = [$this->studyId];
		$adjustConfig = function($study) {
			$study->published = true;
			$study->accessKeys = ['key$key'];
		};
		Main::$defaultPostInput = json_encode($this->createCollection(false, false, $adjustConfig));
		
		$obj = new SaveStudy();
		$this->expectErrorMessage('special characters');
		$obj->exec();
	}
	function test_with_accessKey_with_faulty_format() {
		$this->publishPermissions = [$this->studyId];
		$adjustConfig = function($study) {
			$study->published = true;
			$study->accessKeys = ['1key'];
		};
		Main::$defaultPostInput = json_encode($this->createCollection(false, false, $adjustConfig));
		
		$obj = new SaveStudy();
		$this->expectErrorMessage('start with a character');
		$obj->exec();
	}
	function test_with_changed_accessKeys_without_permission() {
		$adjustConfig = function($study) {
			$study->accessKeys = ['differentKey1'];
		};
		Main::$defaultPostInput = json_encode($this->createCollection(false, false, $adjustConfig));
		
		$obj = new SaveStudy();
		$output = $obj->exec();
		$this->assertEqualConfigs(
			$this->createCollection(true, false),
			$output['json']
		);
	}
	function test_with_changed_accessKeys_and_study_with_removed_publish() {
		$this->publishPermissions = [$this->studyId];
		$adjustConfig = function($study) {
			$study->accessKeys = ['different1'];
		};
		Main::$defaultPostInput = json_encode($this->createCollection(false, false, $adjustConfig));
		
		$obj = new SaveStudy();
		$output = $obj->exec();
		$this->assertEqualConfigs(
			$this->createCollection(true, false, $adjustConfig),
			$output['json']
		);
		$this->assertEquals(-1, $this->statisticsStudyCountChange);
	}
	function test_with_changed_accessKeys_and_with_newly_published_study() {
		$this->publishPermissions = [$this->studyId];
		$this->removeStudyReturn = false;
		$adjustConfig = function($study) {
			$study->published = true;
			$study->accessKeys = ['different1', 'different2', 'different3'];
		};
		Main::$defaultPostInput = json_encode($this->createCollection(false, false, $adjustConfig));
		
		$obj = new SaveStudy();
		$output = $obj->exec();
		$this->assertEqualConfigs(
			$this->createCollection(true, false, $adjustConfig),
			$output['json']
		);
		$this->assertEquals(1, $this->statisticsStudyCountChange);
		$this->assertDataMock('add', [$this->studyId, 'different1'], [$this->studyId, 'different2'], [$this->studyId, 'different3']);
	}
	function test_without_accessKeys_and_with_still_published_study() {
		$this->publishPermissions = [$this->studyId];
		$adjustConfig = function($study) {
			$study->published = true;
			$study->accessKeys = [];
		};
		Main::$defaultPostInput = json_encode($this->createCollection(false, false, $adjustConfig));
		
		$obj = new SaveStudy();
		$output = $obj->exec();
		$this->assertEqualConfigs(
			$this->createCollection(true, false, $adjustConfig),
			$output['json']
		);
		$this->assertEquals(0, $this->statisticsStudyCountChange);
		$this->assertDataMock('add', [$this->studyId, '']);
	}
	
	function test_with_empty_questionnaire_title() {
		Main::$defaultPostInput = json_encode($this->createCollection(false, false, function(stdClass $study) {
			$study->questionnaires[0]->title = '';
		}));
		$this->expectErrorMessage('Questionnaire title is empty');
		$obj = new SaveStudy();
		$obj->exec();
	}
	function test_with_empty_input_name() {
		Main::$defaultPostInput = json_encode($this->createCollection(false, false, function(stdClass $study) {
			$study->questionnaires[0]->pages[0]->inputs[0]->name = '';
		}));
		$this->expectErrorMessage('Input name is empty');
		$obj = new SaveStudy();
		$obj->exec();
	}
	function test_with_input_name_with_special_characters() {
		Main::$defaultPostInput = json_encode($this->createCollection(false, false, function(stdClass $study) {
			$study->questionnaires[0]->pages[0]->inputs[0]->name = 'nameWith%';
		}));
		$this->expectErrorMessage('special characters');
		$obj = new SaveStudy();
		$obj->exec();
	}
	function test_with_identical_input_names() {
		Main::$defaultPostInput = json_encode($this->createCollection(false, false, function(stdClass $study) {
			$study->questionnaires[0]->pages[0]->inputs[1]->name = $study->questionnaires[0]->pages[0]->inputs[0]->name;
		}));
		$this->expectErrorMessage('exists more than once');
		$obj = new SaveStudy();
		$obj->exec();
	}
	function test_with_protected_input_name() {
		Main::$defaultPostInput = json_encode($this->createCollection(false, false, function(stdClass $study) {
			$study->questionnaires[0]->pages[0]->inputs[1]->name = 'studyId';
		}));
		$this->expectErrorMessage('Protected variable name');
		$obj = new SaveStudy();
		$obj->exec();
	}
	
	function test_with_missing_data() {
		$this->assertMissingDataForFeatureObj(SaveStudy::class, [
			'lastChanged' => 'lastChanged'
		], true);
	}
	
	function test_with_broken_data() {
		Main::$defaultPostInput = '-';
		$obj = new SaveStudy();
		$this->expectErrorMessage('Unexpected data');
		$obj->exec();
	}
	
	function test_without_default_study() {
		Main::$defaultPostInput = '{"de": {}}';
		$obj = new SaveStudy();
		$this->expectErrorMessage('No default study');
		$obj->exec();
	}
	
	function test_with_wrong_study_id() {
		$collection = $this->createCollection();
		$collection->_->id = 789999;
		Main::$defaultPostInput = json_encode($collection);
		
		$obj = new SaveStudy();
		$this->expectErrorMessage('Problem with study id');
		$obj->exec();
	}
	
	function test_with_outdated_study() {
		Main::$defaultPostInput = json_encode($this->createCollection());
		$this->setGet([
			'lastChanged' => $this->lastChanged-1
		]);
		$obj = new SaveStudy();
		$this->expectErrorMessage('The study configuration was changed');
		$obj->exec();
	}
}