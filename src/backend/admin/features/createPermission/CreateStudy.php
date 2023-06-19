<?php

namespace backend\admin\features\createPermission;

use backend\admin\features\writePermission\SaveStudy;
use backend\admin\HasCreatePermission;
use backend\Configs;
use backend\exceptions\PageFlowException;

class CreateStudy extends HasCreatePermission {
	function exec(): array {
		if($this->studyId == 0)
			throw new PageFlowException('Missing data');
		
		$studyStore = Configs::getDataStore()->getStudyStore();
		if($studyStore->studyExists($this->studyId))
			throw new PageFlowException('Study already exists');
		
		if(!$this->isAdmin) {
			$accountStore = Configs::getDataStore()->getAccountStore();
			$accountStore->addStudyPermission($_SESSION['account'], $this->studyId, 'write');
			$accountStore->addStudyPermission($_SESSION['account'], $this->studyId, 'read');
			$accountStore->addStudyPermission($_SESSION['account'], $this->studyId, 'msg');
			$accountStore->addStudyPermission($_SESSION['account'], $this->studyId, 'publish');
		}
		$saver = new SaveStudy();
		return $saver->exec();
	}
}