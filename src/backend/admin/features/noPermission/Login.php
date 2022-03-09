<?php

namespace backend\admin\features\noPermission;

use backend\admin\NoPermission;
use backend\Output;
use backend\Permission;

class Login extends NoPermission {
	
	function exec() {
		if(!isset($_POST['user']) || !isset($_POST['pass']))
			Output::error('Missing data');
		$this->checkLoginPost();
		if(Permission::is_loggedIn()) {
			$user = $_POST['user'];
			if(isset($_POST['rememberMe']))
				Permission::create_token($user);
		}
		
		$c = new GetPermissions();
		$c->exec();
	}
}