<?php

namespace backend\admin\features\loggedIn;

use backend\admin\features\loggedIn\GetTokenList;
use backend\admin\IsLoggedIn;
use backend\Permission;

class RemoveToken extends IsLoggedIn {
	
	function exec() {
		$user = Permission::get_user();
		$token_id = $_POST['token_id'];
		Permission::remove_token($user, $token_id);
		
		$c = new GetTokenList();
		$c->exec();
	}
}