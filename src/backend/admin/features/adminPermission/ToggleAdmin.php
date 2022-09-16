<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\PageFlowException;
use backend\Permission;

class ToggleAdmin extends HasAdminPermission {
	
	function exec(): array {
		if(!isset($_POST['accountName']))
			throw new PageFlowException('Missing data');
		else if(Permission::getAccountName() === $_POST['accountName'])
			throw new PageFlowException('You can not remove your own admin permissions');
		
		$accountName = $_POST['accountName'];
		$isAdmin = isset($_POST['admin']);
		
		Configs::getDataStore()->getAccountStore()->setAdminPermission($accountName, $isAdmin);
		
		return [];
	}
}