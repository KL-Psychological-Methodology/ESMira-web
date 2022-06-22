<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Configs;
use backend\PageFlowException;
use backend\Permission;

class ChangePassword extends IsLoggedIn {
	
	function exec(): array {
		if(!isset($_POST['new_pass']))
			throw new PageFlowException('Missing data');
		
		$userStore = Configs::getDataStore()->getUserStore();
		$pass = $_POST['new_pass'];
		
		if($this->isAdmin && isset($_POST['user'])) {
			$user = $_POST['user'];
			if(!$userStore->doesUserExist($user))
				throw new PageFlowException("User $user does not exist");
		}
		else
			$user = Permission::getUser();
		
		if(strlen($pass) < 12)
			throw new PageFlowException('The password needs to have at least 12 characters.');
		
		
		$userStore->setUser($user, $pass);
		
		return [];
	}
}