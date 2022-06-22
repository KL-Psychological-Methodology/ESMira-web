<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Configs;
use backend\CriticalError;
use backend\PageFlowException;

class GetData extends HasReadPermission {
	public function execAndOutput() {
		if(!isset($_GET['q_id']))
			throw new PageFlowException('Missing data');
		Configs::getDataStore()->getResponsesStore()->outputResponsesFile($this->studyId, $_GET['q_id']);
	}
	
	function exec(): array {
		throw new CriticalError('Internal error. GetData can only be used with execAndOutput()');
	}
}