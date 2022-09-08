<?php

namespace test\backend\admin\features\writePermission;

use backend\admin\features\writePermission\EmptyData;
use backend\CreateDataSet;
use backend\dataClasses\StatisticsJsonEntry;
use backend\dataClasses\StudyStatisticsMetadataEntry;
use backend\dataClasses\StudyStatisticsEntry;
use backend\ResponsesIndex;
use backend\subStores\StudyAccessIndexStore;
use backend\subStores\StudyStatisticsMetadataStore;
use backend\subStores\StudyStatisticsStore;
use backend\subStores\StudyStore;
use PHPUnit\Framework\MockObject\Stub;
use stdClass;
use test\testConfigs\BaseWritePermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class EmptyDataTest extends BaseWritePermissionTestSetup {
	private $oneDay = 86400; //in seconds: 60*60*24
	/**
	 * @var stdClass
	 */
	private $config;
	private $internalId = 2345;
	
	public function setUp(): void {
		$this->config = (object) [
			'id' => $this->studyId,
			'questionnaires' => [
				(object) [
					'internalId' => $this->internalId,
					'title' => 'questionnaire',
					'pages' => [
						(object) [
							'inputs' => [
								(object) ['name' => 'input1', 'responseType' => 'photo'],
								(object) ['name' => 'input2'],
							]
						]
					]
				]
			],
			
			'publicStatistics' => (object) [
				'charts' => [
					(object) [
						'dataType' => CreateDataSet::STATISTICS_DATATYPES_SUM,
						'storageType' => CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
						'axisContainer' => [
							(object) [
								'yAxis' => (object) [ //#1
									'variableName' => 'input1',
									'observedVariableIndex' => 0
								]
							],
							(object) [
								'yAxis' => (object) [ //#2
									'variableName' => 'input1',
									'observedVariableIndex' => 1
								]
							]
						]
					]
				],
				'observedVariables' => (object) [
					'input1' => [
						new StudyStatisticsEntry( //#1
							[],
							CreateDataSet::CONDITION_TYPE_ALL,
							CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
							$this->oneDay
						),
						new StudyStatisticsEntry( //#2
							[],
							CreateDataSet::CONDITION_TYPE_ALL,
							CreateDataSet::STATISTICS_STORAGE_TYPE_FREQ_DISTR,
							$this->oneDay
						)
					]
				]
			],
		];
		parent::setUp();
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$studyStatisticsMetadataStore = $this->createMock(StudyStatisticsMetadataStore::class);
		$this->addDataMock($studyStatisticsMetadataStore,  'addMetadataEntry');
		$studyStatisticsMetadataStore
//			->expects($this->once())
			->method('saveChanges');
		$this->createStoreMock(
			'getStudyStatisticsMetadataStore',
			$studyStatisticsMetadataStore,
			$observer
		);

		$studyStatistics= $this->createMock(StudyStatisticsStore::class);
		$this->addDataMock($studyStatistics,  'addEntry');
		$studyStatistics->expects($this->once())
			->method('saveChanges');
		$this->createStoreMock(
			'getStudyStatisticsStore',
			$studyStatistics,
			$observer
		);
		
		
		$store = $this->createDataMock(StudyStore::class, 'getStudyConfig', $this->config);
		$this->addDataMock($store, 'emptyStudy');
		$this->createStoreMock(
			'getStudyStore',
			$store,
			$observer
		);
		
		
		$studyAccessStore = $this->createStub(StudyAccessIndexStore::class);
		$studyAccessStore->method('getStudyIdForQuestionnaireId')
			->willReturn($this->studyId);
		$this->createStoreMock(
			'getStudyAccessIndexStore',
			$studyAccessStore,
			$observer
		);
		
		return $observer;
	}
	
	function test() {
		$obj = new EmptyData();
		$obj->exec();
		
		$responsesIndex = new ResponsesIndex();
		$responsesIndex->addInput('photo', 'input1');
		$responsesIndex->addInput('textInput', 'input2');
		$keys = [
			$this->internalId => $responsesIndex
		];
		$this->assertDataMock('emptyStudy', [$this->studyId, $keys]);
		
		
		$this->assertDataMock('addMetadataEntry',
			[
				'input1',
				new StudyStatisticsEntry( //#1
					[],
					CreateDataSet::CONDITION_TYPE_ALL,
					CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
					$this->oneDay
				)
			],
			[
				'input1',
				new StudyStatisticsEntry( //#2
					[],
					CreateDataSet::CONDITION_TYPE_ALL,
					CreateDataSet::STATISTICS_STORAGE_TYPE_FREQ_DISTR,
					$this->oneDay
				)
			]
		);
		
		
		$this->assertDataMock('addEntry',
			[
				'input1',
				new StatisticsJsonEntry(new StudyStatisticsEntry( //#1
					[],
					CreateDataSet::CONDITION_TYPE_ALL,
					CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED,
					$this->oneDay
				))
			],
			[
				'input1',
				new StatisticsJsonEntry(new StudyStatisticsEntry( //#2
					[],
					CreateDataSet::CONDITION_TYPE_ALL,
					CreateDataSet::STATISTICS_STORAGE_TYPE_FREQ_DISTR,
					$this->oneDay
				))
			]
		);
	}
}