<?php

namespace backend\admin\features\messagePermission;

use backend\admin\HasMessagePermission;
use backend\Configs;

class ListUserWithMessages extends HasMessagePermission {
	function exec(): array {
		return Configs::getDataStore()->getMessagesStore()->getParticipantsWithMessages($this->studyId);
	}
}