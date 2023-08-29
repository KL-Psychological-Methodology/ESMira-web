<?php

namespace backend\admin\features\noPermission;

use backend\admin\NoPermission;
use backend\Configs;
use backend\Permission;

class GetPermissions extends NoPermission {
	
	function exec(): array {
		if(!Permission::isLoggedIn())
			return ['isLoggedIn' => false];
		else {
			if(Permission::isAdmin()) {
				$obj = [
					'isAdmin' => true,
					'canCreate' => true,
					'hasErrors' => Configs::getDataStore()->getErrorReportStore()->hasErrorReports()
				];
			}
			else
				$obj = [
					'permissions' => Permission::getPermissions(),
					'canCreate' => Permission::canCreate()
				];
			$obj['accountName'] = Permission::getAccountName();
			$obj['isLoggedIn'] = true;
			$obj['loginTime'] = time();
			$obj['newMessages'] = Configs::getDataStore()->getMessagesStore()->getStudiesWithUnreadMessagesForPermission();
			
			return $obj;
		}
	}
}