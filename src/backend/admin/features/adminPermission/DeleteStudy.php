<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Files;
use backend\Output;

class DeleteStudy extends HasAdminPermission {
	
	function exec() {
		if($this->study_id == 0)
			Output::error('Missing data');
		
		//remove from study-index
		$study_index = file_exists(Files::get_file_studyIndex()) ? unserialize(file_get_contents(Files::get_file_studyIndex())) : [];
		$this->remove_study_from_index($study_index, $this->study_id);
		$this->write_file(Files::get_file_studyIndex(), serialize($study_index));
		
		
		//remove study data
		$folder_study = Files::get_folder_study($this->study_id);
		if(file_exists($folder_study)) {
			$this->empty_folder($folder_study);
			if(!rmdir($folder_study))
				Output::error("Could not remove $folder_study");
		}
		else
			Output::error("$folder_study does not exist!");
		
		
		//remove from permissions
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		if($permissions) {
			if(isset($permissions['write'])) {
				foreach($permissions['write'] as $user => $studies) {
					foreach($studies as $value => $current_study_id) {
						if($current_study_id === $this->study_id)
							array_splice($permissions['write'][$user], $value, 1);
					}
				}
			}
			if(isset($permissions['read'])) {
				foreach($permissions['read'] as $user => $studies) {
					foreach($studies as $value => $current_study_id) {
						if($current_study_id === $this->study_id)
							array_splice($permissions['read'], $value, 1);
					}
				}
			}
			$this->write_file(Files::get_file_permissions(), serialize($permissions));
		}
		
		Output::successObj($this->study_id);
	}
}