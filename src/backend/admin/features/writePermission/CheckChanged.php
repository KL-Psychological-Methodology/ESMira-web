<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Files;
use backend\Output;

class CheckChanged extends HasWritePermission {
	
	function exec() {
		$sentChanged = (int) $_GET['lastChanged'];
		$file_config = Files::get_file_studyConfig($this->study_id);
		
		if(!file_exists($file_config))
			Output::error('Study does not exist');
		
		
		$realChanged = filemtime($file_config);
		if($realChanged > $sentChanged) {
			$study = file_get_contents($file_config);
			Output::successObj(['lastChanged' => $realChanged, 'json' => $study]);
		}
		else
			Output::successObj(['lastChanged' => $realChanged]);
	}
}