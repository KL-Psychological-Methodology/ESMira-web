<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;

class GetLastActivities extends HasAdminPermission {
	function exec(): array {
		return Configs::getDataStore()->getResponsesStore()->getLastResponseTimestampOfStudies();
	}
}