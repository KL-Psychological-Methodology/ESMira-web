<?php

namespace backend\admin\features\messagePermission;

use backend\admin\HasMessagePermission;
use backend\Configs;
use backend\PageFlowException;

class DeleteMessage extends HasMessagePermission {
	function exec(): array {
		if(!isset($_POST['user']) || !isset($_POST['sent']))
			throw new PageFlowException('Missing data');
		$username = $_POST['user'];
		$sent = (int) $_POST['sent'];
		
		Configs::getDataStore()->getMessagesStore()->deleteMessage($this->studyId, $username, $sent);
		return [];
	}
}