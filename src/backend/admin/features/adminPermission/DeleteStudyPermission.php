<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\PageFlowException;

class DeleteStudyPermission extends HasAdminPermission {
	function exec(): array {
		if(!isset($_POST['accountName']) || !isset($_POST['permission']) || $this->studyId == 0)
			throw new PageFlowException('Missing data');
		
		$accountName = $_POST['accountName'];
		$permCode = $_POST['permission'];
		$accountStore = Configs::getDataStore()->getAccountStore();
		
		switch($permCode) {
			case 'write':
				$accountStore->removeStudyPermission($accountName, $this->studyId, 'write');
				$accountStore->removeStudyPermission($accountName, $this->studyId, 'publish');
				break;
			case 'msg':
			case 'read':
			case 'publish':
				$accountStore->removeStudyPermission($accountName, $this->studyId, $permCode);
				break;
			default:
				throw new PageFlowException('Faulty data');
		}
		
		return [];
	}
}