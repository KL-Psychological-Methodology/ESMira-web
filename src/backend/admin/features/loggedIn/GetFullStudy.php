<?php

namespace backend\admin\features\loggedIn;

use backend\admin\HasWritePermission;
use backend\admin\IsLoggedIn;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\JsonOutput;

class GetFullStudy extends IsLoggedIn {
	public function execAndOutput() {
		$studyStore = Configs::getDataStore()->getStudyStore();
		echo JsonOutput::successString('{"config": ' .$studyStore->getStudyConfigAsJson($this->studyId) .', "languages": ' .$studyStore->getAllLangConfigsAsJson($this->studyId) .'}');
	}
	
	function exec(): array {
		throw new CriticalException('Internal error. GetFullStudy can only be used with execAndOutput()');
	}
}