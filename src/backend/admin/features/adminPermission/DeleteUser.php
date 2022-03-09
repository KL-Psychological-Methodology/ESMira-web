<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Files;
use backend\Output;

class DeleteUser extends HasAdminPermission {
	
	function exec() {
		if(!isset($_POST['user']))
			Output::error('Unexpected data');
		
		$user = $_POST['user'];
		
		//remove permissions:
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		if($permissions) {
			if(isset($permissions[$user])) {
				unset($permissions[$user]);
			}
			
			$this->write_file(Files::get_file_permissions(), serialize($permissions));
		}
		
		$this->removeAdd_in_loginsFile($user);
		
		Output::successObj();
	}
}