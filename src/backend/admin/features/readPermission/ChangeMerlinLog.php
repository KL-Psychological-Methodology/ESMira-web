<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Configs;
use backend\dataClasses\MerlinLogInfo;
use backend\exceptions\PageFlowException;

class ChangeMerlinLog extends HasReadPermission {
	function exec(): array {
		if(!isset($_POST['timestamp']) || !isset($_POST['seen']) || !isset($_POST['note']))
			throw new PageFlowException('Missing data');
		
		$timestamp = (int)$_POST['timestamp'];
		$seen = (bool)$_POST['seen'];
		$note = $_POST['note'];
		
		Configs::getDataStore()->getMerlinLogsStore()->changeMerlinLog($this->studyId, new MerlinLogInfo($timestamp, $note, $seen));
		
		return [];
	}
}