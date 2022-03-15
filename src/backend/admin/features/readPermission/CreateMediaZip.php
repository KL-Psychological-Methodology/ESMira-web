<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Files;
use ZipArchive;

class CreateMediaZip extends HasReadPermission {
	
	function exec() {
		$file_mediaZip = Files::get_file_mediaZip($this->study_id);
		
		if(!file_exists($file_mediaZip)) { //zip was not created or deleted by file_uploads.php, so we create it:
			$zip = new ZipArchive();
			$zip->open($file_mediaZip, ZIPARCHIVE::CREATE);
			
			$images_path = Files::get_folder_images($this->study_id);
			$images_h = opendir($images_path);
			while($user_folder = readdir($images_h)) {
				if($user_folder[0] != '.') {
					$user_id = Files::get_urlFriendly($user_folder);
					
					$user_path = "$images_path/$user_folder";
					$user_h = opendir($user_path);
					while($file = readdir($user_h)) {
						if($file[0] != '.') {
							$zip->addFile("$user_path/$file", "images/$user_id/$file");
						}
					}
					closedir($user_h);
				}
			}
			closedir($images_h);
			$zip->close();
		}
		
		
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' .Files::FILENAME_MEDIA_ZIP);
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.filesize($file_mediaZip));
		
		readfile($file_mediaZip);
	}
}