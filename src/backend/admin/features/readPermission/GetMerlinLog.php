<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\Main;

class GetMerlinLog extends HasReadPermission {
	public function execAndOutput() {
		if(!isset($_GET['timestamp']))
			throw new PageFlowException('Missing data');
		
		$timestamp = (int)$_GET['timestamp'];
		
		Main::setHeader('Content-Type: text/csv');
		echo Configs::getDataStore()->getMerlinLogsStore()->getMerlinLog($this->studyId, $timestamp);
	}
	
	function exec(): array {
		throw new CriticalException('Internal error. GetMerlinLog can only be used with execAndOutput()');
	}
}