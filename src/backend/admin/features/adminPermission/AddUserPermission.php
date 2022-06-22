<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\PageFlowException;

class AddUserPermission extends HasAdminPermission {
	function exec(): array {
		if(!isset($_POST['user']) || !isset($_POST['permission']) || $this->studyId == 0)
			throw new PageFlowException('Missing data');
		
		$user = $_POST['user'];
		$permCode = $_POST['permission'];
		$userStore = Configs::getDataStore()->getUserStore();
		
		switch($permCode) {
			case 'read':
			case 'write':
			case 'msg':
				$userStore->addStudyPermission($user, $this->studyId, $permCode);
				break;
			case 'publish':
				$userStore->addStudyPermission($user, $this->studyId, 'publish');
				$userStore->addStudyPermission($user, $this->studyId, 'write');
				break;
			default:
				throw new PageFlowException('Faulty data');
		}
		
		return [];
	}
}