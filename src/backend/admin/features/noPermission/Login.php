<?php

namespace backend\admin\features\noPermission;

use backend\admin\NoPermission;
use backend\exceptions\PageFlowException;
use backend\Permission;

class Login extends NoPermission {
	
	function exec(): array {
		if(!isset($_POST['accountName']) || !isset($_POST['pass']))
			throw new PageFlowException('Missing data');
		
		$accountName = $_POST['accountName'];
		$pass = $_POST['pass'];
		Permission::login($accountName, $pass);
		
		if(Permission::isLoggedIn() && isset($_POST['rememberMe'])) {
			Permission::createNewLoginToken($accountName);
		}
		
		$c = new GetPermissions();
		return $c->exec();
	}
}