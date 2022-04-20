<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Files;
use backend\Output;

class LoadLangs extends HasWritePermission {
	
	function exec() {
		$folder_langs = Files::get_folder_langs($this->study_id);
		$langBox = [];
		if(file_exists($folder_langs)) {
			$h_folder = opendir($folder_langs);
			while($file = readdir($h_folder)) {
				if($file[0] != '.') {
					$s = file_get_contents($folder_langs .$file);
					$langBox[] = '"' .explode('.', $file)[0] .'":' .$s;
				}
			}
			closedir($h_folder);
		}
		Output::successString('{' .implode(',', $langBox) .'}');
	}
}