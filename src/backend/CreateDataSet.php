<?php
declare(strict_types=1);

namespace backend;

use backend\dataClasses\StudyStatisticsEntry;
use backend\exceptions\CriticalException;
use backend\exceptions\DataSetException;
use backend\subStores\StatisticsStoreWriter;
use backend\subStores\UserDataStore;
use stdClass;

const ONE_DAY = 86400; //in seconds: 60*60*24

class CreateDataSet {
	
	const DATASET_TYPE_JOINED = 'joined';
	const DATASET_TYPE_QUIT = 'quit';
	const DATASET_TYPE_QUESTIONNAIRE = 'questionnaire';
	
	//TODO: change into Strings:
	const CONDITION_TYPE_ALL = 0,
		CONDITION_TYPE_AND = 1,
		CONDITION_TYPE_OR = 2,
		
		CONDITION_OPERATOR_EQUAL = 0,
		CONDITION_OPERATOR_UNEQUAL = 1,
		CONDITION_OPERATOR_GREATER = 2,
		CONDITION_OPERATOR_LESS = 3,
		
		STATISTICS_STORAGE_TYPE_TIMED = 0,
		STATISTICS_STORAGE_TYPE_FREQ_DISTR = 1,
		STATISTICS_STORAGE_TYPE_PER_DATA = 2,
		
		STATISTICS_CHARTTYPES_LINE = 0,
		STATISTICS_CHARTTYPES_LINE_FILLED = 1,
		STATISTICS_CHARTTYPES_BARS = 2,
		STATISTICS_CHARTTYPES_PIE = 3,
		
		STATISTICS_DATATYPES_DAILY = 0,
		STATISTICS_DATATYPES_FREQ_DISTR = 1,
		STATISTICS_DATATYPES_SUM = 2,
		STATISTICS_DATATYPES_XY = 3;
	
	/**
	 * @var UserDataStore
	 */
	public $userDataStore;
	
	public $output = [];
	
	public static function stripOneLineInput(string $s) {
		if(strlen($s) > Configs::get('user_input_max_length'))
			$s = substr($s, 0, Configs::get('user_input_max_length'));
		//it should be ok to save user input mostly "as is" to the filesystem as long as its not used otherwise:
		$s = str_replace('"', '\'', $s);
		
		return str_replace(["\n", "\r"], ' ', $s);
	}
	
	static function saveWebAccess($studyId, $pageName): bool {
		return Configs::getDataStore()->getResponsesStore()->saveWebAccessDataSet(
			$studyId,
			Main::getMilliseconds(),
			$pageName,
			isset($_SERVER["HTTP_REFERER"]) ? self::stripOneLineInput(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST)) : '',
			isset($_SERVER["HTTP_USER_AGENT"]) ? self::stripOneLineInput($_SERVER["HTTP_USER_AGENT"]) : ''
		);
	}
	
	private $metadataIndex = [];
	/**
	 * @var [[StudyStatisticsEntry]]
	 */
	private $statisticMetadataIndex = [];
	
	protected $outputIndex = [];
	/**
	 * @var DataSetCache
	 */
	private $cache;
	private $questionnaireEvents = 0;
	private $joinEvents = 0;
	
	
	/**
	 * @var string
	 */
	private $appType;
	/**
	 * @var string
	 */
	private $appVersion;
	/**
	 * @var string
	 */
	private $userId;
	
	private static function formatDate(int $time) {
		return date('Y/m/d H:i:s', (int) round($time / 1000));
	}
	
	function __construct() {
		$this->cache = new DataSetCache();
	}
	
	private function lineOutputSuccess(int $datasetId) {
		if(!isset($this->outputIndex[$datasetId])) {
			$this->outputIndex[$datasetId] = count($this->output);
			$this->output[] = ['dataSetId' => $datasetId, 'success' => true];
		}
	}
	private function lineOutputError(int $datasetId, string $s) {
		if(isset($this->outputIndex[$datasetId]))
			$this->output[$datasetId] = ['dataSetId' => $datasetId, 'success' => false, 'error' => $s];
		else {
			$this->outputIndex[$datasetId] = count($this->output);
			$this->output[] = ['dataSetId' => $datasetId, 'success' => false, 'error' => $s];
		}
	}
	
	private function getStudyId(stdClass $dataSet): int {
		return (int) $dataSet->studyId;
	}
	private function getDataSetId(stdClass $dataSet): int {
		return (int) $dataSet->dataSetId;
	}
	
	private function conditionIsMet(StudyStatisticsEntry $conditionalStatistics, stdClass $responses): bool {
		if($conditionalStatistics->conditionType == self::CONDITION_TYPE_ALL)
			return true;
		
		$conditionTypeIsOr = $conditionalStatistics->conditionType == self::CONDITION_TYPE_OR;
		$conditionTypeIsAnd = $conditionalStatistics->conditionType == self::CONDITION_TYPE_AND;
		
		foreach($conditionalStatistics->conditions as $condition) {
			switch($condition->operator) {
				case self::CONDITION_OPERATOR_EQUAL:
					$isTrue = $responses->{$condition->key} == $condition->value;
					break;
				case self::CONDITION_OPERATOR_UNEQUAL:
					$isTrue = $responses->{$condition->key} != $condition->value;
					break;
				case self::CONDITION_OPERATOR_GREATER:
					$isTrue = $responses->{$condition->key} >= $condition->value;
					break;
				case self::CONDITION_OPERATOR_LESS:
					$isTrue = $responses->{$condition->key} <= $condition->value;
					break;
				default:
					$isTrue = true;
			}
			
			if($isTrue) {
				if($conditionTypeIsOr) {
					return true;
				}
			}
			else if($conditionTypeIsAnd) {
				return false;
			}
		}
		
		return true;
	}
	
	private function getAnswer(stdClass $dataSet, string $key, array $backwardsAliases = []) {
		if(isset($dataSet->{$key}))
			return self::stripOneLineInput((string) $dataSet->{$key});
		
		else if(isset($dataSet->responses->{$key}))
			return self::stripOneLineInput((string) $dataSet->responses->{$key});
		
		if(isset($backwardsAliases[$key])) {
			$oldKey = $backwardsAliases[$key];
			if(isset($dataSet->{$oldKey}))
				return self::stripOneLineInput((string) $dataSet->{$oldKey});
		
			else if(isset($dataSet->responses->{$oldKey}))
				return self::stripOneLineInput((string) $dataSet->responses->{$oldKey});		
		}
		
		return '';
	}
	
	private function prepareFile(stdClass $dataSet, string $key, callable $getInternalPath, callable $getPublicPath): string {
		$studyId = $this->getStudyId($dataSet);
		$entryId = (int) ($dataSet->entryId ?? 0);
		$identifier = (int) $this->getAnswer($dataSet, $key);
		$datasetId = $this->getDataSetId($dataSet);
		if($identifier != 0) {
			$this->cache->addToFileCache(
				$studyId,
				$getInternalPath($studyId, $this->userId, $entryId, $key),
				$identifier,
				$datasetId
			);
			return $getPublicPath($this->userId, $entryId, $key);
		}
		else
			return '';
	}
	private function getQuestionnaireAnswer(stdClass $dataSet, string $key, array $types, array $backwardsAliases): string {
		if(isset($types[$key])) {
			switch($types[$key]) {
				case 'image':
					return $this->prepareFile(
						$dataSet,
						$key,
						function($studyId, $userId, $entryId, $key) { return Paths::fileImageFromData($studyId, $userId, $entryId, $key); },
						function($userId, $entryId, $key) { return Paths::publicFileImageFromData($userId, $entryId, $key); }
					);
				case 'audio':
					return $this->prepareFile(
						$dataSet,
						$key,
						function($studyId, $userId, $entryId, $key) { return Paths::fileAudioFromData($studyId, $userId, $entryId, $key); },
						function($userId, $entryId, $key) { return Paths::publicFileAudioFromData($userId, $entryId, $key); }
					);
				default:
					return $this->getAnswer($dataSet, $key, $backwardsAliases);
			}
		}
		else
			return $this->getAnswer($dataSet, $key, $backwardsAliases);
	}
	
	/**
	 * @throws DataSetException
	 */
	private function handleQuestionnaireDataSet(stdClass $dataSet) {
		$responses = $dataSet->responses;
		$studyId = $this->getStudyId($dataSet);
		$datasetId = $this->getDataSetId($dataSet);
		
		$questionnaireId = (int) ($dataSet->questionnaireInternalId ?? -1);
		if(!Configs::getDataStore()->getStudyStore()->questionnaireExists($studyId, $questionnaireId)) {
			$questionnaireName = $dataSet->questionnaireName ?? '';
			throw new DataSetException("Questionnaire '$questionnaireName' (id=$questionnaireId) does not exist");
		}
		
		
		$statisticsMetadata = $this->statisticMetadataIndex[$studyId]
			?? ($this->statisticMetadataIndex[$studyId] = Configs::getDataStore()->getStudyStatisticsMetadataStore($studyId)->loadMetadataCollection());
		
		
		//*****
		//fill questionnaire output:
		//*****
		try {
			$questionnaireIndex = $this->cache->getQuestionnaireIndex($studyId, $questionnaireId);
		}
		catch(CriticalException $e) {
			Main::report("Study $studyId seems to be broken. getQuestionnaireIndex() threw:\n".$e->getMessage());
			throw new DataSetException("Study $studyId seems to be broken");
		}
		$types = $questionnaireIndex->types;
		$backwardsAliases = $questionnaireIndex->backwardsAliases;
		$questionnaireData = [];
		
		
		foreach($questionnaireIndex->keys as $key) {
			$answer = $this->getQuestionnaireAnswer($dataSet, $key, $types, $backwardsAliases);
			$questionnaireData[$key] = $answer;
			
			//statistics:
			if(isset($statisticsMetadata[$key])) {
				$currentStatistics = &$statisticsMetadata[$key];
				
				foreach($currentStatistics as $i => $conditionalStatistics) {
					if($this->conditionIsMet($conditionalStatistics, $responses)) {
						$this->cache->addToStatisticsCache(
							$studyId,
							$datasetId,
							new DataSetCacheStatisticsEntry($key, $i, (int) $dataSet->responseTime, $answer)
						);
					}
				}
			}
		}
		
		++$this->questionnaireEvents;
		
		$this->cache->addToQuestionnaireCache($studyId, $questionnaireId, $datasetId, $questionnaireData);
	}
	
	/**
	 * @throws DataSetException
	 */
	private function handleEventDataSet(stdClass $dataSet) {
		$studyId = $this->getStudyId($dataSet);
		$datasetId = $this->getDataSetId($dataSet);
		try {
			$eventIndex = $this->cache->getEventIndex($studyId);
		}
		catch(CriticalException $e) {
			Main::report("Study $studyId seems to be broken. getEventIndex() threw:\n" .$e->getMessage());
			throw new DataSetException("Study $studyId seems to be broken");
		}
		
		$eventsWrite = [];
		foreach($eventIndex->keys as $key) {
			$eventsWrite[$key] = $this->getAnswer($dataSet, $key);
		}
		
		$this->cache->addToEventCache($studyId, $datasetId, $eventsWrite);
	}
	
	/**
	 * @throws DataSetException
	 */
	private function shouldWriteDataSet(stdClass $dataSet): bool {
		$studyId = $this->getStudyId($dataSet);
		$datasetId = $this->getDataSetId($dataSet);
		$eventType = $dataSet->eventType;
		
		//*****
		//check accessKey:
		//*****
		
		try {
			$metadata = $this->metadataIndex[$studyId]
				?? ($this->metadataIndex[$studyId] = Configs::getDataStore()->getStudyMetadataStore($studyId));
			$accessKeys = $metadata->getAccessKeys();
		}
		catch(CriticalException $e) {
			throw new DataSetException($e->getMessage());
		}
		
		if(sizeof($accessKeys) && (!isset($dataSet->accessKey) || !in_array(strtolower(trim($dataSet->accessKey)), $accessKeys)))
			throw new DataSetException('Wrong accessKey: ' .($dataSet->accessKey ?? ''));
		
		
		//*****
		//check token:
		//*****
		try {
			if(!$this->userDataStore->addDataSetForSaving($studyId, $dataSet, $this->appType, $this->appVersion))
				throw new DataSetException('Too many requests in succession');
		}
		catch(CriticalException $e) {
			throw new DataSetException($e->getMessage());
		}
		
		if(isset($dataSet->token) && $this->userDataStore->isOutdated($studyId, (int) $dataSet->token, isset($dataSet->reupload) && $dataSet->reupload)) {
			$this->lineOutputSuccess($datasetId); //data was already sent
			return false;
		}
		
		
		$dataSet->entryId = (string) $this->userDataStore->getDataSetId($studyId);
		
		
		//*****
		//basic checks:
		//*****
		
		
		if(!Main::strictCheckInput($eventType)) {
			$dataSetQuestionnaireName = $dataSet->questionnaireName ?? '';
			throw new DataSetException("Unexpected input! Questionnaire: $dataSetQuestionnaireName; Event-Type: $eventType");
		}
		
		return true;
	}
	
	/**
	 * @throws DataSetException
	 */
	private function handleDataSet(stdClass $dataSet, string $uploaded) {
		$responses = $dataSet->responses;
		
		if(isset($responses->actionScheduledTo))
			$responses->actionScheduledTo_formatted = self::formatDate((int) $responses->actionScheduledTo);
		if(isset($responses->lastInvitation) && $responses->lastInvitation != 0)
			$responses->lastInvitation_formatted = self::formatDate((int) $responses->lastInvitation);
		
		if(isset($dataSet->responseTime))
			$dataSet->responseTime_formatted = self::formatDate((int) $dataSet->responseTime);
		
		$responses->userId = $this->userId;
		$responses->uploaded = $uploaded;
		$responses->appType = $this->appType;
		$responses->appVersion = $this->appVersion;
		
		$dataSet->studyVersion = ((int) ($dataSet->studyVersion ?? 0)).'.'.((int) ($dataSet->studySubVersion ?? 0));
		
		$studyId = $this->getStudyId($dataSet);
		$eventType = $dataSet->eventType;
		$studyStore = Configs::getDataStore()->getStudyStore();
		
		if($studyStore->isLocked($studyId))
			throw new DataSetException('Study is locked');
		
		
		if($this->shouldWriteDataSet($dataSet)) {
			$this->handleEventDataSet($dataSet);
			
			if($eventType === self::DATASET_TYPE_QUESTIONNAIRE)
				$this->handleQuestionnaireDataSet($dataSet);
			else if($eventType === self::DATASET_TYPE_JOINED)
				++$this->joinEvents;
		}
	}
	
	private function updateServerStatistics() {
		Configs::getDataStore()->getServerStatisticsStore()->update(function(StatisticsStoreWriter $statistics) {
			$newUserCount = $this->userDataStore->countNewUser();
			if($newUserCount) {
				$statistics->incrementUser($newUserCount);
				switch($this->appType) {
					case 'Android':
					case 'Android_wasDev':
						$statistics->incrementAndroid($newUserCount);
						break;
					case 'iOS':
					case 'iOS_wasDev':
						$statistics->incrementIos($newUserCount);
						break;
					case 'Web':
					case 'Web-NoJs':
						$statistics->incrementWeb($newUserCount);
						break;
					default:
						return false;
				}
			}
			
			$startOfDay = (int) (floor(time() / ONE_DAY) * ONE_DAY);
			
			$statistics->addDataToDay(
				$startOfDay - ONE_DAY * Configs::get('number_of_saved_days_in_server_statistics'),
				$startOfDay,
				$this->appType,
				$this->appVersion,
				$this->questionnaireEvents,
				$this->joinEvents
			);
			
			return true;
		});
	}
	
	/**
	 * @throws CriticalException
	 */
	function prepare(stdClass $json) {
		if(!isset($json->userId) || !isset($json->appVersion) || !isset($json->appType) || !isset($json->dataset) || !isset($json->serverVersion))
			throw new CriticalException('Unexpected data');
		if($json->serverVersion < Main::ACCEPTED_SERVER_VERSION)
			throw new CriticalException('This app is outdated. Aborting');
		if(!Main::strictCheckInput($json->userId) || !Main::strictCheckInput($json->appType) || !Main::strictCheckInput($json->appVersion))
			throw new CriticalException('Input data not valid');
		
		$uploaded = (string) Main::getMilliseconds();
		$this->userId = $json->userId;
		$this->appType = $json->appType ?? '';
		$this->appVersion = (string) ($json->appVersion ?? ''); //web version sends appVersion as int (because it is essentially the server version)
		
		$this->userDataStore = Configs::getDataStore()->getUserDataStore($this->userId);
		
		
		foreach($json->dataset as $dataSet) {
			try {
				$this->handleDataSet($dataSet, $uploaded);
			}
			catch(DataSetException $e) {
				$this->lineOutputError($this->getDataSetId($dataSet), $e->getMessage());
			}
		}
	}
	
	function exec() {
		Configs::getDataStore()->getResponsesStore()->saveDataSetCache(
			$this->userId,
			$this->cache,
			function(int $datasetId) {
				$this->lineOutputSuccess($datasetId);
			},
			function(int $datasetId, string $msg) {
				$this->lineOutputError($datasetId, $msg);
			});
		
		$this->userDataStore->writeAndClose();
		
		$this->updateServerStatistics();
	}
	
	function close() {
		$this->userDataStore->close();
	}
}