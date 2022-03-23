<?php

namespace backend\admin\features\noPermission;

use backend\admin\NoPermission;
use backend\Output;
use backend\Permission;

class Logout extends NoPermission {
	
	function exec() {
		Permission::set_loggedOut();
		Output::successObj();
	}
}