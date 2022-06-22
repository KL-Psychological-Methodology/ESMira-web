<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;

class ListErrors extends HasAdminPermission {
	function exec(): array {
		return Configs::getDataStore()->getErrorReportStore()->getList();
	}
}