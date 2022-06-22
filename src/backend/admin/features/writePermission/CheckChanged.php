<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Configs;
use backend\PageFlowException;

class CheckChanged extends HasWritePermission {
	function exec(): array {
		if(!isset($_GET['lastChanged']))
			throw new PageFlowException('Missing data');
		
		$studyStore = Configs::getDataStore()->getStudyStore();
		$sentChanged = (int) $_GET['lastChanged'];
		$realChanged = $studyStore->getStudyLastChanged($this->studyId);
		
		if($realChanged > $sentChanged)
			return ['lastChanged' => $realChanged, 'json' => $studyStore->getStudyConfig($this->studyId)];
		else
			return ['lastChanged' => $realChanged];
	}
}