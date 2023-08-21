<?php

namespace backend\admin\features\writePermission;

use backend\Configs;
use backend\dataClasses\StatisticsJsonEntry;
use backend\dataClasses\StudyStatisticsEntry;

class EmptyData extends SaveStudy {
	
	function exec(): array {
		$this->initClass();
		$dataStore = Configs::getDataStore();
		$studyStore = $dataStore->getStudyStore();
		$this->mainStudy = $studyStore->getStudyConfig($this->studyId);
		
		$studyStore->emptyStudy($this->studyId, $this->collectKeys());
		$metadataStore = $dataStore->getStudyStatisticsMetadataStore($this->studyId);
		$studyStatisticsStore = $dataStore->getStudyStatisticsStore($this->studyId);
		
		if(isset($this->mainStudy->publicStatistics->observedVariables)) {
			foreach($this->mainStudy->publicStatistics->observedVariables as $key => $observedVariableJsonArray) {
				foreach($observedVariableJsonArray as $observedVariableJsonEntryJson) {
					$observedVariableJsonEntry = new StudyStatisticsEntry(
						$observedVariableJsonEntryJson->conditions,
						$observedVariableJsonEntryJson->conditionType,
						$observedVariableJsonEntryJson->storageType,
						$observedVariableJsonEntryJson->timeInterval
					);
					$metadataStore->addMetadataEntry($key, $observedVariableJsonEntry);
					$jsonEntry = StatisticsJsonEntry::createNew($observedVariableJsonEntry);
					$studyStatisticsStore->addEntry($key, $jsonEntry);
				}
			}
			$metadataStore->saveChanges();
			$studyStatisticsStore->saveChanges();
		}
		
		return [];
	}
}