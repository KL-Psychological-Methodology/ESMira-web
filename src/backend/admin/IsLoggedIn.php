<?php
namespace backend\admin;

use backend\Base;
use backend\Output;
use backend\Permission;

abstract class IsLoggedIn extends NoPermission {
	protected $study_id;
	protected $is_admin;
	
	protected function empty_folder($path) {
		$h_folder = opendir($path);
		if(!$h_folder) {
			debug_print_backtrace();
			return false;
		}
		while($file = readdir($h_folder)) {
			if($file != '.' && $file != '..') {
				$filename = $path . $file;
				if(is_dir($filename)) {
					if(!$this->empty_folder($filename . '/') || !rmdir($filename))
						return false;
				}
				else {
					if(!unlink($filename))
						return false;
				}
			}
		}
		closedir($h_folder);
		return true;
	}
	
	
	protected function remove_study_from_index(&$key_index, $study_id) {
		$removeCount = 0;
		foreach($key_index as $key => $key_list) {
			if(($key_list_id = array_search($study_id, $key_list)) !== false) {
				unset($key_index[$key][$key_list_id]);
				++$removeCount;
			}
			if(!count($key_index[$key]))
				unset($key_index[$key]);
		}
		return $removeCount;
	}
	
	function __construct() {
		parent::__construct();
		$this->checkLoginPost();
		if(!Permission::is_loggedIn() || !Base::is_init())
			Output::error('No permission');
		
		$this->is_admin = Permission::is_admin();
		$this->study_id = isset($_POST['study_id']) ? (int) $_POST['study_id'] : (isset($_GET['study_id']) ? (int) $_GET['study_id'] : 0);
	}
}

?>