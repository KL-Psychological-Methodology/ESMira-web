<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Files;
use backend\Output;
use backend\Permission;

class GetTokenList extends IsLoggedIn {
	
	function exec() {
		$user = Permission::get_user();
		$folder_token = Files::get_folder_token($user);
		$currentToken = Permission::get_currentToken();
		
		$obj = [];
		if(file_exists($folder_token)) {
			$h_folder = opendir($folder_token);
			while($file = readdir($h_folder)) {
				if($file[0] != '.')
					array_push($obj, ['hash' => $file, 'last_used' => filemtime($folder_token.$file), 'current' => ($file === $currentToken)]);
			}
			closedir($h_folder);
		}
		
		Output::successObj($obj);
	}
}