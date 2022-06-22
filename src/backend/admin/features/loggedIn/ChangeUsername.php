<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Main;
use backend\Configs;
use backend\PageFlowException;
use backend\Permission;

class ChangeUsername extends IsLoggedIn {
	
	function exec(): array {
		if(!isset($_POST['new_user']))
			throw new PageFlowException('Missing data');
		
		$userStore = Configs::getDataStore()->getUserStore();
		
		if($this->isAdmin && isset($_POST['user'])) {
			$user = $_POST['user'];
			if(!$userStore->doesUserExist($user))
				throw new PageFlowException("User $user does not exist");
		}
		else
			$user = Permission::getUser();
		
		$newUser = $_POST['new_user'];
		
		if(strlen($newUser) < 3)
			throw new PageFlowException("Username needs to contain at least 3 characters");
		else if($userStore->doesUserExist($newUser))
			throw new PageFlowException("Username '$newUser' already exists");
		
		$userStore->changeUsername($user, $newUser);
		
		if(Permission::getUser() == $user) {
			$_SESSION['user'] = $newUser;
			if(isset($_COOKIE['user']))
				Main::setCookie('user', $newUser, time()+31536000);
		}
		
		return [];
	}
}