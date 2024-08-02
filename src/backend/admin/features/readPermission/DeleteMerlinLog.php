<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Configs;
use backend\exceptions\PageFlowException;

class DeleteMerlinLog extends HasReadPermission {
	function exec(): array {
		if(!isset($_POST['timestamp']))
			throw new PageFlowException('Missing data');
		
		$timestamp = (int)$_POST['timestamp'];
		
		Configs::getDataStore()->getMerlinLogsStore()->removeMerlinLog($this->studyId, $timestamp);
		return [];
	}
}