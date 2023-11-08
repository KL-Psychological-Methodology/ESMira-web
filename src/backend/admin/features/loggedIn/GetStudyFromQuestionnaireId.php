<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Configs;
use backend\Permission;

class GetStudyFromQuestionnaireId extends IsLoggedIn {
	
	function exec(): array {
		$qId = (int) $_GET['qId'];
		$studyAccessIndexStore = Configs::getDataStore()->getStudyAccessIndexStore();
		return [$studyAccessIndexStore->getStudyIdForQuestionnaireId($qId)];
	}
}