<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;

class GetUsedSpacePerStudy extends HasAdminPermission {
	function exec(): array {
		return Configs::getDataStore()->getStudyStore()->getDirectorySizeOfStudies();
	}
}