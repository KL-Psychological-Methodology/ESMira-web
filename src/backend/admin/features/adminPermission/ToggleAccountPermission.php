<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\PageFlowException;
use backend\Permission;

class ToggleAccountPermission extends HasAdminPermission {
	
	function exec(): array {
		if(!isset($_POST['accountName']))
			throw new PageFlowException('Missing data');
		else if(Permission::getAccountName() === $_POST['accountName'])
			throw new PageFlowException('You can not remove your own admin permissions');
		
		$accountName = $_POST['accountName'];
		
		if(isset($_POST['admin']))
			Configs::getDataStore()->getAccountStore()->setAdminPermission($accountName, (bool) $_POST['admin']);
		if(isset($_POST['create']))
			Configs::getDataStore()->getAccountStore()->setCreatePermission($accountName, (bool) $_POST['create']);
		
		return [];
	}
}