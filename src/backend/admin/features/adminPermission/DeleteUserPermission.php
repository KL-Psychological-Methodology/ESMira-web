<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Files;
use backend\Output;

class DeleteUserPermission extends HasAdminPermission {
	
	private function removePermission(&$permissions, $study_id, $user, $permCode) {
		if(isset($permissions[$user][$permCode]) && ($value = array_search($study_id, $permissions[$user][$permCode])) !== false)
			array_splice($permissions[$user][$permCode], $value, 1);
	}
	
	function exec() {
		if(!isset($_POST['user']) || !isset($_POST['permission']) || $this->study_id == 0)
			Output::error('Missing data');
		
		$user = $_POST['user'];
		$permCode = $_POST['permission'];
		
		
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		if(!$permissions)
			Output::error('No permissions to remove');
		else if(!isset($permissions[$user]))
			Output::error('User has no permissions');
		
		
		switch($permCode) {
			case 'write':
				$this->removePermission($permissions, $this->study_id, $user, 'write');
				$this->removePermission($permissions, $this->study_id, $user, 'publish');
				break;
			case 'msg':
			case 'read':
			case 'publish':
			$this->removePermission($permissions, $this->study_id, $user, $permCode);
				break;
			default:
				Output::error('Faulty data');
		}
		
		
		$this->write_file(Files::get_file_permissions(), serialize($permissions));
		
		Output::successObj();
	}
}