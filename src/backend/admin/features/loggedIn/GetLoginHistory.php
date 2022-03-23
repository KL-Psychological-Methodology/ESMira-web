<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Configs;
use backend\Files;
use backend\Permission;

class GetLoginHistory extends IsLoggedIn {
	
	function exec() {
		$user = Permission::get_user();
		
		$file_history1 = Files::get_file_tokenHistory($user, 1);
		$file_history2 = Files::get_file_tokenHistory($user, 2);
		$exists1 = file_exists($file_history1);
		$exists2 = file_exists($file_history2);

		header('Content-Type: text/csv');
		$csv_delimiter = Configs::get('csv_delimiter');
		echo 'date'.$csv_delimiter.'login'.$csv_delimiter.'ip'.$csv_delimiter.'userAgent';
		if($exists1 && $exists2) {
			if(filemtime($file_history1) < filemtime($file_history2)) {
				readfile($file_history1);
				readfile($file_history2);
			}
			else {
				readfile($file_history2);
				readfile($file_history1);
			}
		}
		else if($exists1)
			readfile($file_history1);
		else if($exists2)
			readfile($file_history2);
		exit();
	}
}