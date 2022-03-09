<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Base;
use backend\Files;
use backend\Output;

class MarkStudyAsUpdated extends HasWritePermission {
	
	function exec() {
		$file = Files::get_file_studyConfig($this->study_id);
		if(!($study = json_decode(file_get_contents($file))))
			Output::error('Unexpected data');
		
		$study->version = isset($study->version) ? $study->version + 1 : 1;
		$study->subVersion = 0;
		$study->new_changes = false;
		
		$this->write_file($file, json_encode($study));
		
		$metadata = Base::get_newMetadata($study);
		$this->write_file(Files::get_file_studyMetadata($this->study_id), serialize($metadata));
		
		$sentChanged = time();
		Output::successObj(['lastChanged' => $sentChanged]);
	}
}