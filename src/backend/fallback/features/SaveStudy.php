<?php

namespace backend\fallback\features;

use backend\Configs;
use backend\exceptions\PageFlowException;
use backend\fallback\FallbackFeature;
use backend\Main;

class SaveStudy extends FallbackFeature
{
	private $studyStore;
	private $studyAccessIndexStore;
	private $studyCollection;
	private $mainStudy;
	private $studyId;

	private function initClass(string $encodedUrl)
	{
		$dataStore = Configs::getDataStore();
		$this->studyAccessIndexStore = $dataStore->getFallbackStudyAccessIndexStore($encodedUrl);
		$this->studyStore = $dataStore->getFallbackStudyStore($encodedUrl);
	}

	private function updateStudyIndex()
	{
		if (!isset($this->mainStudy->accessKeys) || !count($this->mainStudy->accessKeys)) {
			$this->studyAccessIndexStore->add($this->studyId);
			return;
		}

		// Usually this should only be called for studies that have already been checked on the main server
		// so we minimize error reporting and just skip problematic keys
		$alreadyExistingKeys = [];
		foreach ($this->mainStudy->accessKeys as $key => $value) {
			$value = strtolower($value);
			if (isset($alreadyExistingKeys[$value]))
				continue;
			$alreadyExistingKeys[$value] = true;
			foreach ($this->studyCollection as $langStudy) {
				$langStudy->accessKeys[$key] = $value;
			}
			if (empty($value) || !Main::strictCheckInput($value) || !preg_match("/^([a-zA-Z][a-zA-Z0-9]*)$/", $value))
				continue;
			$this->studyAccessIndexStore->add($this->studyId, $value);
		}
	}

	private function publishUnPublish()
	{
		$this->studyAccessIndexStore->removeStudy($this->studyId);
		if ($this->mainStudy->published ?? false) {
			$this->updateStudyIndex();
			$this->studyAccessIndexStore->addQuestionnaireKeys($this->mainStudy);
		}
	}

	private function save()
	{
		$this->studyStore->saveStudy($this->studyCollection, []);
		$this->studyAccessIndexStore->saveChanges();
	}

	function exec(): array
	{
		$this->initClass($this->encodedUrl);
		if (!isset($_POST['studyBundle'])) {
			throw new PageFlowException('Missing data');
		}
		$studyCollectionJson = $_POST['studyBundle'];
		$this->studyCollection = json_decode($studyCollectionJson);
		if (!$this->studyCollection)
			throw new PageFlowException('Unexpected data');

		if (!isset($this->studyCollection->_))
			throw new PageFlowException('No default study language');

		$study = $this->mainStudy = $this->studyCollection->_;

		if (!isset($study->id)) {
			throw new PageFlowException("Problem with study id!");
		}

		$this->studyId = $study->id;

		$this->publishUnPublish();

		$this->save();

		return [];
	}
}
