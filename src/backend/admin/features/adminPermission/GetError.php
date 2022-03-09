<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Files;
use backend\Output;

class GetError extends HasAdminPermission {
	
	function exec() {
		
		if(!isset($_GET['timestamp']) || !isset($_GET['seen']) || !isset($_GET['note']))
			Output::error('Faulty input');
		
		$timestamp = $_GET['timestamp'];
		$seen = $_GET['seen'];
		$note = $_GET['note'];
		
		$file_responses = Files::get_file_errorReport($timestamp, $note, $seen);
		if(file_exists($file_responses)) {
			header('Content-Type: text/csv');
			readfile($file_responses);
			exit();
		}
		else
			Output::error('Not found');
	}
}