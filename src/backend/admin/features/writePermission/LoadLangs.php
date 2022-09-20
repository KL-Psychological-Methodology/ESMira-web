<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\JsonOutput;

class LoadLangs extends HasWritePermission {
	public function execAndOutput() {
		echo JsonOutput::successString(Configs::getDataStore()->getStudyStore()->getAllLangConfigsAsJson($this->studyId));
	}
	
	function exec(): array {
		throw new CriticalException('Internal error. LoadLangs can only be used with execAndOutput()');
	}
}