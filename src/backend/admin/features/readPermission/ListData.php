<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Files;
use backend\Output;

class ListData extends HasReadPermission {
	
	function exec() {
		$l_folder = opendir(Files::get_folder_responses($this->study_id));
		
		$msg = [];
		$events_file = Files::FILENAME_EVENTS.'.csv';
		$webAccess_file = Files::FILENAME_WEB_ACCESS.'.csv';
		while($file = readdir($l_folder)) {
			if($file[0] != '.' && $file != $events_file && $file != $webAccess_file) {
				$msg[] = substr($file, 0, -4);
			}
		}
		Output::successObj($msg);
	}
}