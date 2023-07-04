<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\PageFlowException;

class AddStudyPermission extends HasAdminPermission {
	function exec(): array {
		if(!isset($_POST['accountName']) || !isset($_POST['permission']) || $this->studyId == 0)
			throw new PageFlowException('Missing data');
		
		$accountName = $_POST['accountName'];
		$permCode = $_POST['permission'];
		$accountStore = Configs::getDataStore()->getAccountStore();
		
		switch($permCode) {
			case 'read':
			case 'write':
			case 'msg':
				$accountStore->addStudyPermission($accountName, $this->studyId, $permCode);
				break;
			case 'publish':
				$accountStore->addStudyPermission($accountName, $this->studyId, 'publish');
				$accountStore->addStudyPermission($accountName, $this->studyId, 'write');
				break;
			default:
				throw new PageFlowException('Faulty data');
		}
		
		return [];
	}
}