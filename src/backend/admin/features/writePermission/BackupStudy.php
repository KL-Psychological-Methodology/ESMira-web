<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Base;
use backend\Files;
use backend\Output;

class BackupStudy extends HasWritePermission {
	
	function exec() {
		$study = json_decode(file_get_contents(Files::get_file_studyConfig($this->study_id)));
		
		$metadata_path = Files::get_file_studyMetadata($this->study_id);
		if(!file_exists($metadata_path))
			Output::error('Metadata file does not exist. Save the study to create it.');
		
		$metadata = unserialize(file_get_contents($metadata_path));
		
		function backup($study_id, $identifier) {
			$file_name = Files::get_file_responses($study_id, $identifier);
			$file_backupName = Files::get_file_responsesBackup($study_id, $identifier);
			
			if(!copy($file_name, $file_backupName))
				Output::error("Copying $file_name to $file_backupName failed");
		}
		foreach($study->questionnaires as $questionnaire) {
			backup($this->study_id, $questionnaire->internalId);
		}
		
		backup($this->study_id, Files::FILENAME_EVENTS);
		backup($this->study_id, Files::FILENAME_WEB_ACCESS);
		$metadata['lastBackup'] = Base::get_milliseconds();
		$this->write_file($metadata_path, serialize($metadata));
		Output::successObj();
	}
}