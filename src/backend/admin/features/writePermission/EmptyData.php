<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Files;
use backend\Output;

class EmptyData extends HasWritePermission {
	
	function exec() {
		$responses_folder = Files::get_folder_responses($this->study_id);
		if(file_exists($responses_folder))
			$this->empty_folder($responses_folder);
		else
			Output::error("$responses_folder does not exist");
		
		
		//delete statistics
		$statistics_folder = Files::get_folder_statistics($this->study_id);
		if(file_exists($statistics_folder))
			$this->empty_folder($statistics_folder);
		else
			Output::error("$statistics_folder does not exist");
		
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
		$this->write_indexAndResponses_files($study, Files::FILENAME_EVENTS, self::KEYS_EVENT_RESPONSES);
		$this->write_indexAndResponses_files($study, Files::FILENAME_WEB_ACCESS, self::KEYS_WEB_ACCESS);
		$this->write_statistics($study);
		
		Output::successObj();
	}
}