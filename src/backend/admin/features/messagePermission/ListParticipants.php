<?php

namespace backend\admin\features\messagePermission;

use backend\admin\HasMessagePermission;
use backend\Configs;

class ListParticipants extends HasMessagePermission {
	
	function exec(): array {
		return Configs::getDataStore()->getStudyStore()->getStudyParticipants($this->studyId);
	}
}