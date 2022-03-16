<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Files;

class GetMediaImage extends HasReadPermission {
	
	function exec() {
		if(!isset($_GET['userId']) || !isset($_GET['uploaded']) || !isset($_GET['responseTime']) || !isset($_GET['key']))
			return;
		$user_id = $_GET['userId'];
		$uploaded = (int) $_GET['uploaded'];
		$responseTime = (int) $_GET['responseTime'];
		$key = $_GET['key'];
		
		$images_path = Files::get_file_image_fromData($this->study_id, $user_id, $uploaded, $responseTime, $key);
		
		if(!file_exists($images_path))
			return;
		
		header('Content-Type: image/png');
		header('Content-Length: '.filesize($images_path));
		
		readfile($images_path);
	}
}