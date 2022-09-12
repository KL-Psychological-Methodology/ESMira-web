<?php

namespace backend\admin\features\writePermission;


use backend\admin\HasWritePermission;
use backend\JsonOutput;
use backend\Main;
use backend\Configs;
use backend\CriticalError;

class GetNewId extends HasWritePermission {
	private function getRandomStudyId(): int {
		return rand(1000, 9999);
	}
	private function getRandomQuestionnaireId(): int {
		return rand(10000, 99999);
	}
	
	/**
	 * @throws CriticalError
	 * @internal also used in SaveStudy
	 */
	function createRandomId(bool $forQuestionnaire, array $filtered): int {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$accessIndexStore = Configs::getDataStore()->getStudyAccessIndexStore();
		$i = 0;
		do {
			$id = $forQuestionnaire ? $this->getRandomQuestionnaireId() : $this->getRandomStudyId();
			
			if(++$i > 100)
				throw new CriticalError('Could not find an unused id...');
		} while($studyStore->studyExists($id) || $accessIndexStore->getStudyIdForQuestionnaireId($id) != -1 || isset($filtered[$id]));
		return $id;
	}

	public function execAndOutput() {
		$forQuestionnaire = isset($_GET['for']) && $_GET['for'] === 'questionnaire';
		echo JsonOutput::successObj($this->createRandomId(
			$forQuestionnaire,
			$forQuestionnaire ? json_decode(Main::getRawPostInput(), true) : []
		));
	}
	
	function exec(): array {
		throw new CriticalError('Internal error. GetError can only be used with execAndOutput()');
	}
}