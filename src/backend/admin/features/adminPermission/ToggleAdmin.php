<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\PageFlowException;
use backend\Permission;

class ToggleAdmin extends HasAdminPermission {
	
	function exec(): array {
		if(!isset($_POST['user']))
			throw new PageFlowException('Missing data');
		else if(Permission::getUser() === $_POST['user'])
			throw new PageFlowException('You can not remove your own admin permissions');
		
		$user = $_POST['user'];
		$isAdmin = isset($_POST['admin']);
		
		Configs::getDataStore()->getUserStore()->setAdminPermission($user, $isAdmin);
		
		return [];
	}
}