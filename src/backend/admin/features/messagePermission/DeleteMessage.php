<?php

namespace backend\admin\features\messagePermission;

use backend\admin\HasMessagePermission;
use backend\Configs;
use backend\exceptions\PageFlowException;

class DeleteMessage extends HasMessagePermission {
	function exec(): array {
		if(!isset($_POST['userId']) || !isset($_POST['sent']))
			throw new PageFlowException('Missing data');
		$userId = $_POST['userId'];
		$sent = (int) $_POST['sent'];
		
		Configs::getDataStore()->getMessagesStore()->deleteMessage($this->studyId, $userId, $sent);
		return [];
	}
}