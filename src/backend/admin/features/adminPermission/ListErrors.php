<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Files;
use backend\Output;

class ListErrors extends HasAdminPermission {
	
	function exec() {
		$msg = [];
		$h_folder = opendir(Files::get_folder_errorReports());
		while($file = readdir($h_folder)) {
			if($file[0] != '.') {
				$msg[] = Files::interpret_errorReport_file($file);
			}
		}
		closedir($h_folder);
		Output::successObj($msg);
	}
}