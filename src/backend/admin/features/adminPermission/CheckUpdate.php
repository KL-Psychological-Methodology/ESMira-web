<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\Output;

class CheckUpdate extends HasAdminPermission {
	
	function exec() {
		$currentVersion = $_GET['version'];
		$json=file_get_contents(Configs::get('url_update_packageInfo'));
		$version = json_decode($json)->version;
		
		if($currentVersion != $version) {
			$changelog=file_get_contents(Configs::get('url_update_changelog'));
			Output::successObj(['has_update' => true, 'newVersion' => $version, 'changelog' => $changelog]);
		}
		else
			Output::successObj(['has_update' => false]);
	}
}