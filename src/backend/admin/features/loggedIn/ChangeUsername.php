<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Base;
use backend\Files;
use backend\Output;
use backend\Permission;

class ChangeUsername extends IsLoggedIn {
	
	function exec() {
		if(!isset($_POST['new_user']))
			Output::error('Unexpected data');
		
		if($this->is_admin && isset($_POST['user']))
			$user = $_POST['user'];
		else
			$user = Permission::get_user();
		
		$new_user = $_POST['new_user'];
		
		if($this->check_userExists($new_user))
			Output::error("Username '$new_user' already exists");
		
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		if($permissions) {
			if(isset($permissions[$user])) {
				$permissions[$new_user] = $permissions[$user];
				unset($permissions[$user]);
			}
			
			$this->write_file(Files::get_file_permissions(), serialize($permissions));
		}
		$this->removeAdd_in_loginsFile($user, function($user, $newPass) use($new_user) { return "$new_user:$newPass";});
		
		$folder_token = Files::get_folder_token($user);
		if(file_exists($folder_token))
			rename($folder_token, Files::get_folder_token($new_user));
		
		if(Permission::get_user() == $user) {
			$_SESSION['user'] = $new_user;
			if(isset($_COOKIE['user']))
				Base::create_cookie('user', $_COOKIE['user'] = $new_user, time()+31536000);
		}
		
		Output::successObj();
	}
}