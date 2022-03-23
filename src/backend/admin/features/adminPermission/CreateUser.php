<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Files;
use backend\Output;
use backend\Permission;

class CreateUser extends HasAdminPermission {
	
	function exec() {
		if(!isset($_POST['new_user']) || !isset($_POST['pass']) || strlen($_POST['pass']) <= 3)
			Output::error('Unexpected data');
		
		$user = $_POST['new_user'];
		if($this->check_userExists($user))
			Output::error("Username '$user' already exists");
		
		$pass = Permission::get_hashed_pass($_POST['pass']);
		
		
		if(!file_put_contents(Files::get_file_logins(), $user .':' .$pass ."\n", FILE_APPEND))
			Output::error('Login data could not be saved');
		else
			Output::successObj(['username' => $user]);
	}
}