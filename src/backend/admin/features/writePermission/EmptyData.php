<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Files;
use backend\Output;

class EmptyData extends HasWritePermission {
	
	function empty_folderOrError($folder) {
		if(file_exists($folder))
			$this->empty_folder($folder);
		else
			Output::error("$folder does not exist");
	}
	
	function exec() {
		$this->empty_folderOrError(Files::get_folder_responses($this->study_id));
		$this->empty_folderOrError(Files::get_folder_statistics($this->study_id));
		$this->empty_folderOrError(Files::get_folder_images($this->study_id));
		$this->empty_folderOrError(Files::get_folder_pendingUploads($this->study_id));
		
		
		$mediaZip = Files::get_file_mediaZip($this->study_id);
		if(file_exists($mediaZip))
			unlink($mediaZip);
		
		
		//recreate study
		$study_file = Files::get_file_studyConfig($this->study_id);
		if(file_exists($study_file))
			$study_json = file_get_contents($study_file);
		else
			Output::error("$study_file does not exist");
		
		
		if(!($study = json_decode($study_json)))
			Output::error('Unexpected data');
		
		$study_index = file_exists(Files::get_file_studyIndex()) ? unserialize(file_get_contents(Files::get_file_studyIndex())) : [];
		$keys = $this->checkUnique_and_collectKeys($study, $study_index);
		foreach($study->questionnaires as $i => $q) {
			$this->write_indexAndResponses_files($study, $q->internalId, $keys[$i]);
		}
		$this->write_indexAndResponses_files($study, Files::FILENAME_EVENTS, ['keys' => self::KEYS_EVENT_RESPONSES, 'types' => []]);
		$this->write_indexAndResponses_files($study, Files::FILENAME_WEB_ACCESS, ['keys' => self::KEYS_WEB_ACCESS, 'types' => []]);
		$this->write_statistics($study);
		
		Output::successObj();
	}
}