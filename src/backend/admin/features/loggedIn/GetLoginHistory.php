<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Main;
use backend\Configs;
use backend\CriticalError;
use backend\Permission;

class GetLoginHistory extends IsLoggedIn {
	
	public function execAndOutput() {
		$accountName = Permission::getAccountName();
		Main::setHeader('Content-Type: text/csv');
		echo Configs::getDataStore()->getAccountStore()->getLoginHistoryCsv($accountName);
	}
	
	function exec(): array {
		throw new CriticalError('Internal error. GetLoginHistory can only be used with execAndOutput()');
	}
}