<?php

namespace backend\admin\features\messagePermission;

use backend\admin\HasMessagePermission;
use backend\Files;
use backend\Output;

class ListParticipants extends HasMessagePermission {
	
	function exec() {
		$usernames_folder = Files::get_folder_userData($this->study_id);
		$usernames = [];
		if(file_exists($usernames_folder)) {
			$h_folder = opendir($usernames_folder);
			while($file = readdir($h_folder)) {
				if($file[0] != '.') {
					$usernames[] = Files::get_urlFriendly($file);
				}
			}
			closedir($h_folder);
		}
		Output::successObj($usernames);
	}
}