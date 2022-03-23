<?php

namespace backend\admin\features\noPermission;

use backend\admin\NoPermission;
use backend\Base;
use backend\Configs;
use backend\Files;
use backend\Output;
use backend\Permission;

class GetPermissions extends NoPermission {
	
	private function list_additionalPermissions($is_admin, &$userPermissions) {
		$new_messages = [];
		$needsBackup = [];
		$lastActivities = [];
		$count = 0;
		$h_folder = opendir(Files::get_folder_studies());
		$writePermissions = !$is_admin && isset($userPermissions['write']) ? $userPermissions['write'] : [];
		$msgPermissions = !$is_admin && isset($userPermissions['msg']) ? $userPermissions['write'] : [];
		while($study_id = readdir($h_folder)) {
			if($study_id[0] === '.' || $study_id === Files::FILENAME_STUDY_INDEX)
				continue;
			
			//new messages:
			if($is_admin || in_array($study_id, $msgPermissions)) {
				$studyDir = Files::get_folder_messages_unread($study_id);
				if(!file_exists($studyDir))
					continue;
				$h_study = opendir($studyDir);
				while($file = readdir($h_study)) {
					if($file[0] != '.') {
						$new_messages[$study_id] = true;
						++$count;
						break;
					}
				}
			}
			
			//need backup:
			if($is_admin || in_array($study_id, $writePermissions)) {
				$metadata_path = Files::get_file_studyMetadata($study_id);
				if(file_exists($metadata_path)) {
					$metadata = unserialize(file_get_contents($metadata_path));
					if(isset($metadata['published']) && $metadata['published'] && (!isset($metadata['lastBackup']) || Base::get_milliseconds() - $metadata['lastBackup'] > Configs::get('backup_interval_days') * 24*60*60*1000)) {
						array_push($needsBackup, (int) $study_id);
					}
				}
			}
			
			//last activity:
			$events_path = Files::get_file_responses($study_id, Files::FILENAME_EVENTS);
			if(file_exists($events_path))
				array_push($lastActivities, [(int) $study_id, filemtime($events_path)]);
		}
		closedir($h_folder);
		$new_messages['count'] = $count;
		
		
		$userPermissions['new_messages'] = $new_messages;
		$userPermissions['needsBackup_list'] = $needsBackup;
		$userPermissions['lastActivities'] = $lastActivities;
	}
	
	function exec() {
		if(!Base::is_init())
			Output::successObj(['init_esmira' => true]);
		else if(!Permission::is_loggedIn())
			Output::successObj(['isLoggedIn' => false]);
		else {
			if(Permission::is_admin()) {
				$obj = ['is_admin' => true];
				$has_errors = false;
				$h_folder = opendir(Files::get_folder_errorReports());
				while($file = readdir($h_folder)) {
					if($file[0] != '_' && $file[0] != '.') {
						$has_errors = true;
					}
				}
				closedir($h_folder);
				$obj['has_errors'] = $has_errors;
				
				$this->list_additionalPermissions(true, $obj);
			}
			else {
				$obj = ['permissions' => Permission::get_permissions()];
				$this->list_additionalPermissions(false, $obj);
			}
			$obj['username'] = Permission::get_user();
			$obj['isLoggedIn'] = true;
			$obj['loginTime'] = time();
			Output::successObj($obj);
		}
	}
}