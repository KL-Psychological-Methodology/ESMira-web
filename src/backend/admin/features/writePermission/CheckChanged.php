<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Configs;
use backend\exceptions\PageFlowException;

class CheckChanged extends HasWritePermission {
	function exec(): array {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$realChanged = $studyStore->getStudyLastChanged($this->studyId);
		return [$realChanged];
	}
}