<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Files;
use backend\Output;

class ChangeError extends HasAdminPermission {
	
	function exec() {
		if(!isset($_POST['timestamp']) || !isset($_POST['seen']) || !isset($_POST['note']))
			Output::error('Faulty input');
		
		$timestamp = $_POST['timestamp'];
		$seen = $_POST['seen'];
		$note = $_POST['note'];
		
		$filename = Files::get_file_errorReport($timestamp, $note, $seen);
		
		if(!file_exists($filename))
			Output::error('Error report does not exist!');
		
		if(isset($_POST['new_seen']))
			$seen = $_POST['new_seen'];
		if(isset($_POST['new_note']))
			$note = $_POST['new_note'];
		
		$new_filename = Files::get_file_errorReport($timestamp, $note, $seen);
		
		if(rename($filename, $new_filename))
			Output::successObj();
		else
			Output::error("Could not change $filename");
	}
}