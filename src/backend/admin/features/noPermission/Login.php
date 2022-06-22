<?php

namespace backend\admin\features\noPermission;

use backend\admin\NoPermission;
use backend\PageFlowException;
use backend\Permission;

class Login extends NoPermission {
	
	function exec(): array {
		if(!isset($_POST['user']) || !isset($_POST['pass']))
			throw new PageFlowException('Missing data');
		
		$user = $_POST['user'];
		$pass = $_POST['pass'];
		Permission::login($user, $pass);
		
		if(Permission::isLoggedIn() && isset($_POST['rememberMe'])) {
			Permission::createNewLoginToken($user);
		}
		
		$c = new GetPermissions();
		return $c->exec();
	}
}