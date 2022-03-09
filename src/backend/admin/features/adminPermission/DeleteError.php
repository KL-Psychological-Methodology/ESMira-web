<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Files;
use backend\Output;

class DeleteError extends HasAdminPermission {
	
	function exec() {
		if(!isset($_POST['timestamp']) || !isset($_POST['seen']) || !isset($_POST['note']))
			Output::error('Faulty input');
		
		$timestamp = $_POST['timestamp'];
		$seen = $_POST['seen'];
		$note = $_POST['note'];
		
		$filename = Files::get_file_errorReport($timestamp, $note, $seen);
		
		if(file_exists($filename) && unlink($filename))
			Output::successObj();
		else
			Output::error("Could not remove $filename");
	}
}