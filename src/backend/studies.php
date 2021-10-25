<?php
header('Content-Type: application/json;charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');
require_once 'php/configs.php';
require_once 'php/files.php';
require_once 'php/global_json.php';


function list_fromIndex(&$studies_json, $key) {
	$lang = get_lang(false);
	$key_index = unserialize(file_get_contents(FILE_STUDY_INDEX));
	if(isset($key_index[$key])) {
		$ids = $key_index[$key];
		
		foreach($ids as $id) {
			if($lang) {
				$path_lang = get_file_langConfig($id, $lang);
				if(file_exists($path_lang)) {
					$studies_json[] = file_get_contents($path_lang);
					continue;
				}
			}
			$path = get_file_studyConfig($id);
			if(file_exists($path))
				$studies_json[] = file_get_contents($path);
		}
	}
}


function get_specificStudyJson($id) {
	$path = get_file_studyConfig($id);
	if(file_exists($path))
		return file_get_contents($path);
	else
		return [];
}

$studies_json = [];

if(!file_exists(FOLDER_DATA))
	return success('[' .implode($studies_json, ',') .']');

if(isset($_GET['is_loggedIn'])) {
	require_once 'php/permission_fu.php';
	
	if(is_loggedIn()) {
		if(is_admin()) {
			$h_folder = opendir(FOLDER_STUDIES);
			while($folder = readdir($h_folder)) {
				if($folder[0] != '.' && $folder != FILENAME_STUDY_INDEX) {
					$s = file_get_contents(get_file_studyConfig($folder));
					$studies_json[] = $s;
				}
			}
			closedir($h_folder);
		}
		else {
			$notTwice_index = [];
			$permissions = get_permissions();
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
		return error('Not logged in.');
}
else if(isset($_GET['access_key']) && strlen($_GET['access_key'])) {
	$key = strtolower($_GET['access_key']);
	list_fromIndex($studies_json, $key);
}

else {
	list_fromIndex($studies_json, '~open');
}

return success('[' .implode($studies_json, ',') .']');

?>
