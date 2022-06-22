<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\PageFlowException;

class CreateUser extends HasAdminPermission {
	
	function exec(): array {
		if(!isset($_POST['new_user']) || !isset($_POST['pass']) || strlen($_POST['pass']) <= 3)
			throw new PageFlowException('Missing data');
		
		$user = $_POST['new_user'];
		$pass = $_POST['pass'];
		$userStore = Configs::getDataStore()->getUserStore();
		if($userStore->doesUserExist($user))
			throw new PageFlowException("Username '$user' already exists");
		
		
		$userStore->setUser($user, $pass);
		return ['username' => $user];
	}
}