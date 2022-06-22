<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Configs;

class ListData extends HasReadPermission {
	
	function exec(): array {
		return Configs::getDataStore()->getResponsesStore()->getResponseFilesList($this->studyId);
	}
}