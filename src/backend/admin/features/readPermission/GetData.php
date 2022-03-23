<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Files;
use backend\Output;

class GetData extends HasReadPermission {
	
	function exec() {
		$file_responses = Files::get_file_responses($this->study_id, $_GET['q_id']);
		if(file_exists($file_responses)) {
			header('Content-Type: text/csv');
			readfile($file_responses);
			exit();
		}
		else
			Output::error('Not found');
	}
}