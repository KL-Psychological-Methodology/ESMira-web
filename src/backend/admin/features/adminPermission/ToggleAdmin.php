<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Files;
use backend\Output;
use backend\Permission;

class ToggleAdmin extends HasAdminPermission {
	
	function exec() {
		if(!isset($_POST['user']))
			Output::error('Missing data');
		else if(Permission::get_user() === $_POST['user'])
			Output::error('You can not remove your own admin permissions');
		
		$user = $_POST['user'];
		$admin = isset($_POST['admin']);
		
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		if(!$permissions)
			$permissions = [];
		
		if(!isset($permissions[$user]))
			$permissions[$user] = ['admin' => $admin];
		else
			$permissions[$user]['admin'] = $admin;
		
		
		$this->write_file(Files::get_file_permissions(), serialize($permissions));
		
		Output::successObj();
	}
}