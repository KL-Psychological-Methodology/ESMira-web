<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;

class ListUsers extends HasAdminPermission {
	function exec(): array {
		$userStore = Configs::getDataStore()->getUserStore();
		$userList = $userStore->getUserList();
		$output = [];
		foreach($userList as $username) {
			$permissions = $userStore->getPermissions($username);
			$permissions['username'] = $username;
			$output[] = $permissions;
		}
		
		return $output;
	}
}