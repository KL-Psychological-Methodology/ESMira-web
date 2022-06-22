<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Configs;

class IsFrozen extends HasWritePermission {
	
	function exec(): array {
		return [Configs::getDataStore()->getStudyStore()->isLocked($this->studyId)];
	}
}