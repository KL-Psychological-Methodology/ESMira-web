<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Files;
use backend\Output;

class ListUsers extends HasAdminPermission {
	
	function exec() {
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		$userList = [];
		if(!($h = fopen(Files::get_file_logins(), 'r')))
			Output::error("Could not open logins file");
		
		while(!feof($h)) {
			$line = substr(fgets($h), 0, -1);
			if($line == '')
				continue;
			$data = explode(':', $line);
			$username = $data[0];
			
			if(isset($permissions[$username])) {
				$user = $permissions[$username];
				$user['username'] = $username;
				$userList[] = $user;
			}
			else {
				$userList[] = ['username' => $username];
			}
		}
		Output::successObj($userList);
	}
}