<?php

namespace backend\admin\features\noPermission;

use backend\admin\NoPermission;
use backend\Permission;

class Logout extends NoPermission {
	
	function exec(): array {
		Permission::setLoggedOut();
		return [];
	}
}