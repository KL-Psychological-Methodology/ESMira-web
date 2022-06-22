<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Main;
use backend\Configs;
use backend\CriticalError;
use backend\PageFlowException;

class GetError extends HasAdminPermission {
	public function execAndOutput() {
		if(!isset($_GET['timestamp']))
			throw new PageFlowException('Missing data');
		
		$timestamp = (int) $_GET['timestamp'];
		
		Main::setHeader('Content-Type: text/csv');
		echo Configs::getDataStore()->getErrorReportStore()->getErrorReport($timestamp);
	}
	
	function exec(): array {
		throw new CriticalError('Internal error. GetError can only be used with execAndOutput()');
	}
}