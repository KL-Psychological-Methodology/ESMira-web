<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;

class ListAccounts extends HasAdminPermission {
	function exec(): array {
		$accountStore = Configs::getDataStore()->getAccountStore();
		$userList = $accountStore->getAccountList();
		$output = [];
		foreach($userList as $accountName) {
			$permissions = $accountStore->getPermissions($accountName);
			$permissions['accountName'] = $accountName;
			$output[] = $permissions;
		}
		
		return $output;
	}
}