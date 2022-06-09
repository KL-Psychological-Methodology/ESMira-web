<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\Files;
use backend\Output;

class DownloadUpdate extends HasAdminPermission {
	
	function exec() {
		$preRelease = (bool) $_GET['preRelease'];
		$versionString = $_GET['version'];
		
		$pathUpdate = Files::get_file_serverUpdate();
		if(file_exists($pathUpdate))
			unlink($pathUpdate);
		
		if($preRelease)
			$url = sprintf(Configs::get('url_update_preReleaseZip'), $versionString);
		else
			$url = sprintf(Configs::get('url_update_releaseZip'), $versionString);
		
		$res = fopen($url, 'r');
		if(!$res)
			Output::error('Downloading update failed. Nothing was changed');
		
		if(!file_put_contents($pathUpdate, $res))
			Output::error('Saving update file failed. Nothing was changed');
		
		Output::successObj();
	}
}