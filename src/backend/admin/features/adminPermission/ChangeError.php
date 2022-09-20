<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\dataClasses\ErrorReportInfo;
use backend\exceptions\PageFlowException;

class ChangeError extends HasAdminPermission {
	
	function exec(): array {
		if(!isset($_POST['timestamp']) || !isset($_POST['seen']) || !isset($_POST['note']))
			throw new PageFlowException('Missing data');
		
		$timestamp = (int) $_POST['timestamp'];
		$seen = (bool) $_POST['seen'];
		$note = $_POST['note'];
		
		$errorStore = Configs::getDataStore()->getErrorReportStore();
		
		$errorStore->changeErrorReport(new ErrorReportInfo($timestamp, $note, $seen));
		
		return [];
	}
}