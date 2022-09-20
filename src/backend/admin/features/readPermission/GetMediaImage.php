<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;

class GetMediaImage extends HasReadPermission {
	public function execAndOutput() {
		if(!isset($_GET['userId']) || !isset($_GET['entryId']) || !isset($_GET['key']))
			throw new PageFlowException('Missing data');
		$userId = $_GET['userId'];
		$entryId = (int) $_GET['entryId'];
		$key = $_GET['key'];
		
		Configs::getDataStore()->getResponsesStore()->outputImageFromResponses($this->studyId, $userId, $entryId, $key);
	}
	
	function exec(): array {
		throw new CriticalException('Internal error. GetMediaImage can only be used with execAndOutput()');
	}
}