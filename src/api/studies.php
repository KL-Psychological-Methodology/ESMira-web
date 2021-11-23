<?php
require_once '../backend/autoload.php';

use backend\Files;
use backend\Output;
use backend\Base;
use backend\Permission;

if(!Base::is_init())
	Output::error('ESMira is not ready!');

function list_fromIndex(&$studies_json, $key) {
	$lang = Base::get_lang(false);
	$key_index = unserialize(file_get_contents(Files::get_file_studyIndex()));
	if(isset($key_index[$key])) {
		$ids = $key_index[$key];
		
		foreach($ids as $id) {
			if($lang) {
				$path_lang = Files::get_file_langConfig($id, $lang);
				if(file_exists($path_lang)) {
					$studies_json[] = file_get_contents($path_lang);
					continue;
				}
			}
			$path = Files::get_file_studyConfig($id);
			if(file_exists($path))
				$studies_json[] = file_get_contents($path);
		}
	}
}


function get_specificStudyJson($id) {
	$path = Files::get_file_studyConfig($id);
	if(file_exists($path))
		return file_get_contents($path);
	else
		return [];
}

$studies_json = [];

if(isset($_GET['is_loggedIn'])) {
	if(Permission::is_loggedIn()) {
		if(Permission::is_admin()) {
			$h_folder = opendir(Files::get_folder_studies());
			while($folder = readdir($h_folder)) {
				if($folder[0] != '.' && $folder != Files::FILENAME_STUDY_INDEX) {
					$s = file_get_contents(Files::get_file_studyConfig($folder));
					$studies_json[] = $s;
				}
			}
			closedir($h_folder);
		}
		else {
			$notTwice_index = [];
			$permissions = Permission::get_permissions();
			if(isset($permissions['read'])) {
				foreach($permissions['read'] as $id) {
					$notTwice_index[$id] = true;
					$studies_json[] = get_specificStudyJson($id);
				}
			}
			if(isset($permissions['msg'])) {
				foreach($permissions['msg'] as $id) {
					if(!isset($notTwice_index[$id])) {
						$notTwice_index[$id] = true;
						$studies_json[] = get_specificStudyJson($id);
					}
				}
			}
			if(isset($permissions['write'])) {
				foreach($permissions['write'] as $id) {
					if(!isset($notTwice_index[$id]))
						$studies_json[] = get_specificStudyJson($id);
				}
			}
		}
	}
	else
		Output::error('Not logged in.');
}
else if(isset($_GET['access_key']) && strlen($_GET['access_key'])) {
	$key = strtolower($_GET['access_key']);
	list_fromIndex($studies_json, $key);
}

else {
	list_fromIndex($studies_json, '~open');
}

Output::successString('[' .implode($studies_json, ',') .']');