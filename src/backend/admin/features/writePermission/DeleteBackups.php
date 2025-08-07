<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Configs;
use backend\exceptions\PageFlowException;

class DeleteBackups extends HasWritePermission {

	function exec(): array {
		if ($this->studyId == 0)
			throw new PageFlowException('Missing data');

		$dataStore = Configs::getDataStore();
		$studyStore = $dataStore->getStudyStore();
		$studyStore->deleteBackups($this->studyId);

		return [];
	}

}