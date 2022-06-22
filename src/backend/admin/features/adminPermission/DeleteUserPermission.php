<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\PageFlowException;

class DeleteUserPermission extends HasAdminPermission {
	function exec(): array {
		if(!isset($_POST['user']) || !isset($_POST['permission']) || $this->studyId == 0)
			throw new PageFlowException('Missing data');
		
		$user = $_POST['user'];
		$permCode = $_POST['permission'];
		$userStore = Configs::getDataStore()->getUserStore();
		
		switch($permCode) {
			case 'write':
				$userStore->removeStudyPermission($user, $this->studyId, 'write');
				$userStore->removeStudyPermission($user, $this->studyId, 'publish');
				break;
			case 'msg':
			case 'read':
			case 'publish':
				$userStore->removeStudyPermission($user, $this->studyId, $permCode);
				break;
			default:
				throw new PageFlowException('Faulty data');
		}
		
		return [];
	}
}