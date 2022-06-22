<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\PageFlowException;

class DeleteUser extends HasAdminPermission {
	
	function exec(): array {
		if(!isset($_POST['user']))
			throw new PageFlowException('Missing data');
		
		$user = $_POST['user'];
		
		Configs::getDataStore()->getUserStore()->removeUser($user);
		
		return [];
	}
}