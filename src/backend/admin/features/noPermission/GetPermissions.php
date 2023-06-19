<?php

namespace backend\admin\features\noPermission;

use backend\admin\NoPermission;
use backend\Configs;
use backend\Permission;

class GetPermissions extends NoPermission {
	
	function exec(): array {
		if(!Configs::getDataStore()->isInit())
			return ['init_esmira' => true];
		else if(!Permission::isLoggedIn())
			return ['isLoggedIn' => false];
		else {
			if(Permission::isAdmin()) {
				$obj = [
					'is_admin' => true,
					'can_create' => true,
					'has_errors' => Configs::getDataStore()->getErrorReportStore()->hasErrorReports()
				];
			}
			else
				$obj = [
					'permissions' => Permission::getPermissions(),
					'can_create' => Permission::canCreate()
				];
			$obj['accountName'] = Permission::getAccountName();
			$obj['isLoggedIn'] = true;
			$obj['loginTime'] = time();
			$obj['new_messages'] = Configs::getDataStore()->getMessagesStore()->getStudiesWithUnreadMessagesForPermission();
			
			return $obj;
		}
	}
}