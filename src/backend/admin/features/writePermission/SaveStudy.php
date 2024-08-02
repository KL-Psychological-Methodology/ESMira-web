<?php
declare(strict_types=1);

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Configs;
use backend\CreateDataSet;
use backend\dataClasses\StatisticsJsonEntry;
use backend\dataClasses\StudyStatisticsEntry;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\Main;
use backend\Permission;
use backend\ResponsesIndex;
use backend\subStores\StatisticsStoreWriter;
use backend\subStores\StudyAccessIndexStore;
use backend\subStores\StudyStore;
use stdClass;


const ONE_DAY = 86400; //in seconds: 60*60*24

class SaveStudy extends HasWritePermission {
	/**
	 * @var stdClass
	 */
	protected $mainStudy;
	/**
	 * @var stdClass
	 */
	private $studyCollection;
	/**
	 * @var StudyAccessIndexStore
	 */
	private $studyAccessIndexStore;
	/**
	 * @var StudyStore
	 */
	private $studyStore;
	/**
	 * @var stdClass
	 */
	private $publicObservedVariables;
	private $publicObservedIndex = [];
	private $uniqueInputNames = [];
	private $identicalValueInAllLanguagesIndex = [
		'version' => true,
		'subVersion' => true,
		'internalId' => true
	];
	
	
	public static function getConditionString(string $key, int $storageType, int $timeInterval, array $conditions): string {
		$a = [];
		foreach($conditions as $c) {
			$a[] = $c->key . ($c->operator ?? CreateDataSet::CONDITION_OPERATOR_EQUAL) . $c->value;
		}
		sort($a);
		return $key .$storageType .$timeInterval .implode('', $a);
	}
	
	private function extractObservedEntryFromAxis(stdClass $axisData, array &$index, stdClass $observedVariables, int $storageType, int $timeInterval) {
		$key = $axisData->variableName ?? '';
		if(!strlen($key))
			return;
		
		$conditionString = self::getConditionString($key, $storageType, $timeInterval, $axisData->conditions ?? []);
		if(isset($index[$conditionString]))
			$axisData->observedVariableIndex = $index[$conditionString];
		else {
			if(!isset($observedVariables->{$key}))
				$observedVariables->{$key} = [];
			
			$keyBox = &$observedVariables->{$key};
			$keyBox[] = new StudyStatisticsEntry(
				$axisData->conditions ?? [],
				$axisData->conditionType ?? CreateDataSet::CONDITION_TYPE_ALL,
				$storageType,
				$timeInterval
			);
			
			$index[$conditionString] = $axisData->observedVariableIndex = sizeof($keyBox)-1;
		}
	}
	
	private function extractObservedEntriesFromChart(stdClass $chart, stdClass $currentObservedVariables, array &$currentIndex) {
		$dataType = isset($chart->dataType) ? (int) $chart->dataType : CreateDataSet::STATISTICS_DATATYPES_DAILY;
		
		switch($dataType) {
			case CreateDataSet::STATISTICS_DATATYPES_SUM:
			case CreateDataSet::STATISTICS_DATATYPES_DAILY:
				$timeInterval = ONE_DAY;
				$storageType = CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED;
				break;
			case CreateDataSet::STATISTICS_DATATYPES_FREQ_DISTR:
				$timeInterval = 0;
				$storageType = CreateDataSet::STATISTICS_STORAGE_TYPE_FREQ_DISTR;
				break;
			case CreateDataSet::STATISTICS_DATATYPES_XY:
			default:
				$timeInterval = 0;
				$storageType = CreateDataSet::STATISTICS_STORAGE_TYPE_PER_DATA;
				break;
		}
		
		//main variables:
		foreach($chart->axisContainer ?? [] as $axisContainer) {
			$this->extractObservedEntryFromAxis(
				$axisContainer->yAxis ?? new stdClass(), $currentIndex, $currentObservedVariables, $storageType, $timeInterval
			);
			
			if($dataType == CreateDataSet::STATISTICS_DATATYPES_XY) {
				$this->extractObservedEntryFromAxis(
					$axisContainer->xAxis ?? new stdClass(), $currentIndex, $currentObservedVariables, $storageType, $timeInterval
				);
			}
			else if(isset($axisContainer->xAxis))
				$axisContainer->xAxis->observedVariableIndex = -1; //not really necessary but makes it easier to spot in debug if data is redundant
		}
		
		//public variables
		if(isset($chart->displayPublicVariable) && $chart->displayPublicVariable) {
			foreach($chart->publicVariables as $axis) {
				$this->extractObservedEntryFromAxis(
					$axis->yAxis ?? new stdClass(), $this->publicObservedIndex, $this->publicObservedVariables, $storageType, $timeInterval
				);
				if($dataType == CreateDataSet::STATISTICS_DATATYPES_XY) {
					$this->extractObservedEntryFromAxis(
						$axis->xAxis ?? new stdClass(), $this->publicObservedIndex, $this->publicObservedVariables, $storageType, $timeInterval
					);
				}
			}
		}
		
		$chart->storageType = $storageType;
	}
	private function createObservedVariable(bool $isPublic) {
		$statisticsName = $isPublic ? 'publicStatistics' : 'personalStatistics';
		$statistics = $this->mainStudy->{$statisticsName};
		
		
		if($isPublic) {
			$observedVariables = &$this->publicObservedVariables;
			$index = &$this->publicObservedIndex;
		}
		else {
			$observedVariables = new stdClass;
			$index = [];
		}
		
		foreach($statistics->charts as $chart) {
			$this->extractObservedEntriesFromChart($chart, $observedVariables, $index);
		}
		
		$statistics->observedVariables = $observedVariables;
		
	}
	
	private function syncConfigs(/*mixed*/ $original, /*mixed*/ $target) {
		if(is_object($original)) {
			$has = function(/*mixed*/ $parent, /*mixed*/ $key) {
				return isset($parent->{$key});
			};
			$get = function(/*mixed*/ $parent, /*mixed*/ $key) {
				return $parent->{$key};
			};
			$set = function(/*mixed*/ $parent, /*mixed*/ $key, /*mixed*/ $value) {
				$parent->{$key} = $value;
			};
		}
		else {
			$has = function(array $parent, /*mixed*/ $key) {
				return isset($parent[$key]);
			};
			$get = function(array $parent, /*mixed*/ $key) {
				return $parent[$key];
			};
			$set = function(array &$parent, /*mixed*/ $key, /*mixed*/ $value) {
				$parent[$key] = $value;
			};
		}
		foreach($original as $key => $originalChild) {
			if(!$has($target, $key)) {
				$set($target, $key, $originalChild);
				continue;
			}
			else
				$targetChild = $get($target, $key);
			
			if(is_object($originalChild) || is_array($originalChild))
				$this->syncConfigs($originalChild, $targetChild);
			else if(array_key_exists($key, $this->identicalValueInAllLanguagesIndex) && $originalChild != $targetChild)
				$set($target, $key, $originalChild);
		}
	}
	
	/**
	 * @throws PageFlowException
	 */
	private function updateStudyIndex() {
		if(!isset($this->mainStudy->accessKeys) || !count($this->mainStudy->accessKeys)) {
			$this->studyAccessIndexStore->add($this->studyId);
			return;
		}
		
		$alreadyExistingKeys = [];
		foreach($this->mainStudy->accessKeys as $key => $value) {
			$value = strtolower($value);
			if(isset($alreadyExistingKeys[$value]))
				throw new PageFlowException("The access key \"$value\" was added several times to this study.");
			
			$alreadyExistingKeys[$value] = true;
			foreach($this->studyCollection as $langStudy) {
				$langStudy->accessKeys[$key] = $value;
			}
			if(empty($value))
				throw new PageFlowException("Access key is empty");
			else if(!Main::strictCheckInput($value))
				throw new PageFlowException("No special characters are allowed in access keys:\n'$value'");
			else if(!preg_match("/^([a-zA-Z][a-zA-Z0-9]*)$/", $value))
				throw new PageFlowException("Access keys need to start with a character.");
			else
				$this->studyAccessIndexStore->add($this->studyId, $value);
		}
	}
	
	/**
	 * @throws CriticalException
	 * @throws PageFlowException
	 */
	private function publishUnPublish() {
		//
		//publish / unPublish study
		//
		if($this->isAdmin || Permission::hasPermission($this->studyId, 'publish')) {
			$wasRemoved = $this->studyAccessIndexStore->removeStudy($this->studyId);
			
			if($this->mainStudy->published ?? false) {
				$this->updateStudyIndex();
				$this->studyAccessIndexStore->addQuestionnaireKeys($this->mainStudy);
				
				//update server statistics:
				if(!$wasRemoved) {
					Configs::getDataStore()->getServerStatisticsStore()->update(function(StatisticsStoreWriter $statistics) {
						$statistics->incrementStudies();
					});
				}
			}
			else if($wasRemoved) {
				Configs::getDataStore()->getServerStatisticsStore()->update(function(StatisticsStoreWriter $statistics) {
					$statistics->decrementStudies();
				});
			}
		}
		else {
			$oldStudy = $this->studyStore->getStudyConfig($this->studyId);
			
			foreach($this->studyCollection as $langStudy) {
				$langStudy->accessKeys = $oldStudy->accessKeys ?? [];
				$langStudy->published = $oldStudy->published ?? false;
			}
		}
	}
	
	/**
	 * @throws PageFlowException
	 */
	private function uniqueNameOrThrow($name, $questionnaireTitle) {
		if(!strlen($name))
			throw new PageFlowException('Input name is empty!');
		else if(!Main::strictCheckInput($name))
			throw new PageFlowException("No special characters are allowed in variable names. \n'$name' detected in questionnaire: $questionnaireTitle");
		else if(isset($this->uniqueInputNames[$name]))
			throw new PageFlowException("Variable name exists more than once: '$name'. First detected in questionnaire: '" . $this->uniqueInputNames[$name] . "'. Detected again in questionnaire: '$questionnaireTitle'");
		else if(in_array($name, KEYS_EVENT_RESPONSES) || in_array($name, KEYS_QUESTIONNAIRE_BASE_RESPONSES))
			throw new PageFlowException("Protected variable name: $name \nPlease choose another variable name.\nDetected in questionnaire: $questionnaireTitle");
	}
	
	
	private function setNewInternalId($newInternalId, $questionnaire) {
		$oldInternalId = $questionnaire->internalId ?? 0;
		$questionnaire->internalId = $newInternalId;
		foreach($this->studyCollection as $langStudy) {
			foreach($langStudy->questionnaires ?? [] as $q) {
				foreach($q->actionTriggers ?? [] as $actionTrigger) {
					foreach($actionTrigger->eventTriggers ?? [] as $eventTrigger) {
						if($eventTrigger->specificQuestionnaireInternalId ?? -1 == $oldInternalId)
							$eventTrigger->specificQuestionnaireInternalId = $newInternalId;
					}
				}
			}
		}
	}
	
	
	/**
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
	private function correctInternalIds() {
		//Note: When a questionnaire is deleted, its internalId will stay in the index until the study is unpublished or deleted.
		//The only solution I can think of would be to loop through the complete index every time a study is saved.
		//But since this case will rarely happen and probably wont ever be a problem and the loop can be an expensive operation, we just ignore this problem.
		
		$studyId = $this->mainStudy->id;
		
		$uniqueInternalIds = [];
		
		foreach($this->mainStudy->questionnaires as $questionnaire) {
			//make sure internalIds are unique:
			$studyIdForQuestionnaireId = $this->studyAccessIndexStore->getStudyIdForQuestionnaireId($questionnaire->internalId ?? -1);
			if(
				!isset($questionnaire->internalId) ||
				$questionnaire->internalId == -1 ||
				isset($uniqueInternalIds[$questionnaire->internalId]) ||
				($studyIdForQuestionnaireId != -1 && $studyIdForQuestionnaireId != $studyId)
			) {
				$idCreator = new GetNewId();
				$newInternalId = $idCreator->createRandomId(true, $uniqueInternalIds);
				$this->setNewInternalId($newInternalId, $questionnaire);
			}
			else
				$uniqueInternalIds[$questionnaire->internalId] = true;
			
			//check questionnaire:
			if(!isset($questionnaire->title) || !strlen($questionnaire->title))
				throw new PageFlowException('Questionnaire title is empty!');
		}
	}
	
	/**
	 * @throws PageFlowException
	 */
	private function getQuestionnaireIndex(stdClass $questionnaire): ResponsesIndex {
		$questionnaireIndex = new ResponsesIndex();
		
		foreach($questionnaire->pages ?? [] as $page) {
			foreach($page->inputs ?? [] as $input) {
				if(!isset($input->name))
					continue;
				
				$this->uniqueNameOrThrow($input->name, $questionnaire->title);
				$this->uniqueInputNames[$input->name] = $questionnaire->title;
				$questionnaireIndex->addInput($input);
			}
		}
		
		foreach($questionnaire->sumScores ?? [] as $score) {
			if(!isset($score->name))
				continue;
			
			$this->uniqueNameOrThrow($score->name, $questionnaire->title);
			$this->uniqueInputNames[$score->name] = $questionnaire->title;
			$questionnaireIndex->addName($score->name);
		}

		foreach($questionnaire->virtualInputs ?? [] as $virtualInput) {
			if(!is_string($virtualInput))
				continue;

			$this->uniqueNameOrThrow($virtualInput, $questionnaire->title);
			$this->uniqueInputNames[$virtualInput] = $questionnaire->title;
			$questionnaireIndex->addName($virtualInput);
		}

		return $questionnaireIndex;
	}
	
	/**
	 * @throws PageFlowException
	 */
	protected function collectKeys(): array {
		$keys = [];
		foreach($this->mainStudy->questionnaires as $questionnaire) {
			$keys[$questionnaire->internalId] = $this->getQuestionnaireIndex($questionnaire);
		}
		return $keys;
	}
	
	/**
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
	private function save() {
		$this->studyStore->saveStudy($this->studyCollection, $this->collectKeys());
		$this->studyAccessIndexStore->saveChanges();
		$this->saveStatistics();
	}
	
	private function createNamedStructureFromStatistics(stdClass $statistics, array $metadata): array {
		$index = [];
		if(!empty($statistics) || !empty($metadata)) {
			foreach($statistics as $value => $jsonKeyBox) {
				foreach($jsonKeyBox as $i => $jsonEntry) {
					$metadataEntry = $metadata[$value][$i];
					$conditionString = SaveStudy::getConditionString($value, $jsonEntry->storageType, $metadataEntry->timeInterval, $metadataEntry->conditions);
					$index[$conditionString] = $jsonEntry;
				}
			}
		}
		return $index;
	}
	
	/**
	 * @throws CriticalException
	 */
	protected function saveStatistics() {
		$dataStore = Configs::getDataStore();
		$metadataStore = $dataStore->getStudyStatisticsMetadataStore($this->mainStudy->id);
		$studyStatisticsStore = $dataStore->getStudyStatisticsStore($this->mainStudy->id);
		
		if(isset($this->mainStudy->publicStatistics->observedVariables) && $this->mainStudy->publicStatistics->observedVariables != new stdClass()) {
			//order of statistics might have changed. So we have to create an index with more information:
			$existingIndex = $this->createNamedStructureFromStatistics(
				$studyStatisticsStore->getStatistics(),
				$metadataStore->loadMetadataCollection()
			);
			foreach($this->mainStudy->publicStatistics->observedVariables as $key => $observedVariableJsonArray) {
				foreach($observedVariableJsonArray as $observedVariableJsonEntry) {
					$metadataStore->addMetadataEntry($key, $observedVariableJsonEntry);
					
					
					$jsonEntry = StatisticsJsonEntry::createNew($observedVariableJsonEntry);
					
					$conditionString = self::getConditionString($key, $observedVariableJsonEntry->storageType, $observedVariableJsonEntry->timeInterval, $observedVariableJsonEntry->conditions);
					if(isset($existingIndex[$conditionString])) { //copy existing values over to new obj:
						$oldEntry = $existingIndex[$conditionString];
						$jsonEntry->data = $oldEntry->data ?? [];
						$jsonEntry->entryCount = $oldEntry->entryCount ?? 0;
						$jsonEntry->timeInterval = $oldEntry->timeInterval ?? Configs::get('smallest_timed_distance');
					}
//						else {
//							//TODO: extract statistics from already existing data
//						}
					
					$studyStatisticsStore->addEntry($key, $jsonEntry);
				}
			}
		}
		$metadataStore->saveChanges();
		$studyStatisticsStore->saveChanges();
	}
	
	protected function initClass() {
		$dataStore = Configs::getDataStore();
		$this->studyAccessIndexStore = $dataStore->getStudyAccessIndexStore();
		$this->studyStore = $dataStore->getStudyStore();
	}
	
	function exec(): array {
		if(!isset($_GET['lastChanged']))
			throw new PageFlowException('Missing data');
		$this->initClass();
		$studyCollectionJson = Main::getRawPostInput();
		$this->studyCollection = json_decode($studyCollectionJson);
		if(!$this->studyCollection)
			throw new PageFlowException('Unexpected data');
		
		if(!isset($this->studyCollection->_))
			throw new PageFlowException('No default study language');
		
		$study = $this->mainStudy = $this->studyCollection->_;
		
		if(!isset($study->id) || $study->id != $this->studyId)
			throw new PageFlowException("Problem with study id! $this->studyId != $study->id");
		
		
		if($this->studyStore->getStudyLastChanged($this->studyId) > (int) $_GET['lastChanged'])
			throw new PageFlowException('The study configuration was changed (by another account?) since you last loaded it. You can not save your changes. Please reload the page.');
		
		
		//
		//Creating observable variables and statistics
		//
		$this->publicObservedVariables = new stdClass;
		
		$this->createObservedVariable(true);
		$this->createObservedVariable(false);
		
		
		//
		//set study version
		//
		if(!isset($study->version) || $study->version === 0) {
			$study->version = 1;
			$study->subVersion = 0;
		}
		else {
			$study->new_changes = true;
			$study->subVersion = ($study->subVersion ?? 0) + 1;
		}
		
		
		//
		//make sure study everything valid
		//
		$this->correctInternalIds();
		foreach($this->studyCollection as $langCode => $langStudy) {
			if($langCode != '_')
				$this->syncConfigs($this->mainStudy, $langStudy);
		}
		
		
		
		//
		//publish / unPublish
		//
		$this->publishUnPublish();
		
		
		//
		//saving
		//
		$this->save();
		
        $studyMetadataStore = Configs::getDataStore()->getStudyMetadataStore($study->id);
		return [
            'metaData' => [
                'owner' => $studyMetadataStore->getOwner(),
                'lastSavedBy' => $studyMetadataStore->getLastSavedBy(),
                'lastSavedAt' => $studyMetadataStore->getLastSavedAt(),
                'createdTimestamp' => $studyMetadataStore->getCreatedTimestamp(),
            ],
            'json' => $this->studyCollection
        ];
	}
}