<?php

namespace backend\admin\features\messagePermission;

use backend\admin\HasMessagePermission;
use backend\Configs;
use backend\exceptions\PageFlowException;

class ListMessages extends HasMessagePermission {
	
	function exec(): array {
		if(!isset($_GET['user']))
			throw new PageFlowException('Missing data');
		$userId = $_GET['user'];
		return Configs::getDataStore()->getMessagesStore()->getMessagesList($this->studyId, $userId);
	}
}