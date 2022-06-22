<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Configs;

class MarkStudyAsUpdated extends HasWritePermission {
	
	function exec(): array {
		Configs::getDataStore()->getStudyStore()->markStudyAsUpdated($this->studyId);
		return ['lastChanged' => time()];
	}
}