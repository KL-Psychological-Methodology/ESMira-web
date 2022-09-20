<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\PageFlowException;

class DeleteStudy extends HasAdminPermission {
	
	function exec(): array {
		if($this->studyId == 0)
			throw new PageFlowException('Missing data');
		
		$saver = Configs::getDataStore()->getStudyStore();
		$saver->delete($this->studyId);
		
		return [$this->studyId];
	}
}