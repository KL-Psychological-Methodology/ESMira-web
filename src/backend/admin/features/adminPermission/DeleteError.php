<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\PageFlowException;

class DeleteError extends HasAdminPermission {
	
	function exec(): array {
		if(!isset($_POST['timestamp']))
			throw new PageFlowException('Missing data');
		
		$timestamp = (int) $_POST['timestamp'];
		
		$errorStore = Configs::getDataStore()->getErrorReportStore();
		$errorStore->removeErrorReport($timestamp);
		return [];
	}
}