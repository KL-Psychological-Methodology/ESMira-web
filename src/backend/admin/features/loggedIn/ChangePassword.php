<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Output;
use backend\Permission;

class ChangePassword extends IsLoggedIn {
	
	function exec() {
		if(!isset($_POST['new_pass']))
			Output::error('Unexpected data');
		
		$pass = $_POST['new_pass'];
		
		if($this->is_admin && isset($_POST['user']))
			$user = $_POST['user'];
		else
			$user = Permission::get_user();
		
		if(strlen($pass) < 12)
			Output::error('The password needs to have at least 12 characters.');
		
		$passHash = Permission::get_hashed_pass($pass);
		if($this->removeAdd_in_loginsFile($user, function($user) use ($passHash) { return "$user:$passHash";}))
			Output::successObj();
		else
			Output::error('User does not exist.');
	}
}