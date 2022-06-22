<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Configs;

class BackupStudy extends HasWritePermission {
	
	function exec(): array {
		$studyStore = Configs::getDataStore()->getStudyStore();
		$studyStore->backupStudy($this->studyId);
		return [];
	}
}