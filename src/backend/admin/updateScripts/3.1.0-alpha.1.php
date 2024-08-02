<?php

use backend\Configs;
use backend\fileSystem\PathsFS;
use backend\fileSystem\DataStoreFS;
use backend\fileSystem\loader\ResponsesIndexLoader;
use backend\fileSystem\subStores\StudyMetadataStoreFS;
use backend\FileSystemBasics;
use backend\ResponsesIndex;

/*
Reason for update:
    3.1.0 changes the way multiple choice items ("list_multiple") gets represented in datasets.
    MC inupts generate two types of outputs. Fist, a single column concatenating the item texts of all checked answer options. Second, a matrix with one column for each answer option, filled with 0 or 1.
    Previously the answer option columns where named according to the item text (e.g., "color~red", "color~blue"), which creates incompatibilities if the item is translated to multiple languages.
    This version changes the naming to use indices (e.g., "color~1", "color~2").
Procedure:
    For each study containing one or more multiple choice items, this script does the following:
    1. Generate an associative array matching the old variable names with the new ones.
    2. For questionnaires containing multiple choice items:
        2.1. Backing up existing response files.
        2.2. Updating the header of existing response files.
    3. Update the ResponseIndex of the study, including the matching array.
Note:
    This update works mostly by using search and replace in both the response files and the study config files. In edge cases this could technically lead to broken files.
    However, the replaced strings are specific enough that this is highly unlikely, as this would require very unusual text input / variable naming (the replaced strings contain a '~', which is unlikely to occur in the files outside of the specific searched contexts).
*/

$buildMatchTable = function(stdClass $questionnaire): array {
	$matchTable = [];
	foreach($questionnaire->pages as $page) {
		foreach($page->inputs as $input) {
			if(isset($input->responseType) && $input->responseType == "list_multiple") {
				$index = 1;
				$inputName = $input->name;
				foreach($input->listChoices as $choice) {
					if(is_string($choice)) {
						$matchTable["$inputName~$choice"] = "$inputName~$index";
					} elseif(is_array($choice)) {
						foreach($choice as $choiceLangVariant) {
							$matchTable["$inputName~$choiceLangVariant"] = "$inputName~$index";
						}
					}
					$index++;
				}
			}
		}
	}
	return $matchTable;
};

$buildReverseMatchTable = function(stdClass $questionnaire, string $defaultLang): array {
	$matchTable = [];
	foreach($questionnaire->pages as $page) {
		foreach($page->inputs as $input) {
			if(isset($input->responseType) && $input->responseType == "list_multiple") {
				$index = 1;
				$inputName = $input->name;
				foreach($input->listChoices as $choice) {
					if(is_string($choice)) {
						$matchTable["$inputName~$index"] = "$inputName~$choice";
					} elseif(is_array($choice)) {
						foreach($choice as $lang => $choiceLangVariant) {
							if($lang == $defaultLang) {
								$matchTable["$inputName~$index"] = "$inputName~$choiceLangVariant";
							}
						}
					}
					$index++;
				}
			}
		}
	}
	return $matchTable;
};

$studyStore = Configs::getDataStore()->getStudyStore();

foreach($studyStore->getStudyIdList() as $studyId) {
	$studyJson = $studyStore->getStudyConfigAsJson($studyId);
	$containsMcInput = strpos($studyJson, "list_multiple") != false;
	if(!$containsMcInput) {
		continue;
	}
	$study = $studyStore->getStudyConfig($studyId);
	$studyStore->backupStudy($studyId);
	$csvSeparator = Configs::get('csv_delimiter');
	
	foreach($study->questionnaires as $questionnaire) {
		// Update response file headers
		
		$matchTable = $buildMatchTable($questionnaire);
		$questionnaireId = $questionnaire->internalId;
		$responsePath = PathsFS::fileResponses($studyId, $questionnaireId);
		$questionnaireResponse = file_get_contents($responsePath);
		$questionnaireResponse = strtr($questionnaireResponse, $matchTable);
		FileSystemBasics::writeFile($responsePath, $questionnaireResponse);
		
		
		// Update ResponsesIndex
		
		$responseIndex = unserialize(file_get_contents(PathsFS::fileResponsesIndex($studyId, (string)$questionnaireId)));
		foreach($responseIndex->keys as &$key) {
			$key = strtr($key, $matchTable);
		}
		ResponsesIndexLoader::exportFile($studyId, $questionnaireId, new ResponsesIndex($responseIndex->keys, $responseIndex->types, $buildReverseMatchTable($questionnaire, $study->defaultLang ?? "")));
	}
}