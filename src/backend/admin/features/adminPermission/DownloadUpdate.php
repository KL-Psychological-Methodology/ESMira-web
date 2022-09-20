<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\Paths;
use backend\exceptions\PageFlowException;

class DownloadUpdate extends HasAdminPermission {
	
	function exec(): array {
		$preRelease = (bool) $_GET['preRelease'];
		$versionString = $_GET['version'];
		
		$pathUpdate = Paths::FILE_SERVER_UPDATE;
		if(file_exists($pathUpdate))
			unlink($pathUpdate);
		
		$url = sprintf(Configs::get($preRelease ? 'url_update_preReleaseZip' : 'url_update_releaseZip'), $versionString);
		
		$res = @fopen($url, 'r');
		if(!$res)
			throw new PageFlowException("Downloading update from $url failed. Nothing was changed");
		
		if(!@file_put_contents($pathUpdate, $res))
			throw new PageFlowException("Saving update file to $pathUpdate failed. Nothing was changed");
		
		return [];
	}
}