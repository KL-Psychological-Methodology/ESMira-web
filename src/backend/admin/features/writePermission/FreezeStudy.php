<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Configs;

class FreezeStudy extends HasWritePermission {
	
	function exec(): array {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$studyStore->lockStudy($this->studyId, isset($_GET['frozen']));
		return [$studyStore->isLocked($this->studyId)];
	}
}