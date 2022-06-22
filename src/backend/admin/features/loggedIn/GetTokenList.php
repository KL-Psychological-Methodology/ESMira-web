<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Configs;
use backend\Permission;

class GetTokenList extends IsLoggedIn {
	
	function exec(): array {
		$username = Permission::getUser();
		return Configs::getDataStore()->getLoginTokenStore()->getLoginTokenList($username);
	}
}