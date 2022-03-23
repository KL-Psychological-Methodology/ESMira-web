<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Files;
use backend\Output;

class AddUserPermission extends HasAdminPermission {
	
	private function addPermission(&$permissions, $study_id, $user, $permCode) {
		if(!isset($permissions[$user]))
			$permissions[$user] = [$permCode => [$study_id]];
		else if(!isset($permissions[$user][$permCode]))
			$permissions[$user][$permCode] = [$study_id];
		else if(!in_array($study_id, $permissions[$user][$permCode]))
			$permissions[$user][$permCode][] = $study_id;
	}
	
	function exec() {
		if(!isset($_POST['user']) || !isset($_POST['permission']) || $this->study_id == 0)
			Output::error('Missing data');
		
		$user = $_POST['user'];
		$permCode = $_POST['permission'];
		
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		if(!$permissions)
			$permissions = [];
		
		switch($permCode) {
			case 'read':
			case 'write':
			case 'msg':
				$this->addPermission($permissions, $this->study_id, $user, $permCode);
				break;
			case 'publish':
				$this->addPermission($permissions, $this->study_id, $user, 'publish');
				$this->addPermission($permissions, $this->study_id, $user, 'write');
				break;
			default:
				Output::error('Faulty data');
		}
		
		$this->write_file(Files::get_file_permissions(), serialize($permissions));
		Output::successObj();
	}
}