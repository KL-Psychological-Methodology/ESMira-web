<?php

namespace backend\admin\features\messagePermission;

use backend\admin\HasMessagePermission;
use backend\Main;
use backend\Configs;
use backend\exceptions\PageFlowException;

class MessageSetRead extends HasMessagePermission {
	
	function exec(): array {
		if(!($json = json_decode(Main::getRawPostInput())))
			throw new PageFlowException('Unexpected data');
		else if(!isset($json->userId) || !isset($json->timestamps))
			throw new PageFlowException('Missing data');
		
		Configs::getDataStore()->getMessagesStore()->setMessagesAsRead($this->studyId, $json->userId, $json->timestamps);
		return [];
	}
}