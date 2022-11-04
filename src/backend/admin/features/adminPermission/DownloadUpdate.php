<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\Paths;
use backend\exceptions\PageFlowException;

class DownloadUpdate extends HasAdminPermission {
	
	function exec(): array {
		if(!isset($_POST['url']))
			throw new PageFlowException('Missing data');
		
		$url = $_POST['url'];
		
		$pathUpdate = Paths::FILE_SERVER_UPDATE;
		if(file_exists($pathUpdate))
			unlink($pathUpdate);
		
		$res = @fopen($url, 'r');
		if(!$res)
			throw new PageFlowException("Downloading update from $url failed. Nothing was changed");
		
		if(!@file_put_contents($pathUpdate, $res)) {
			fclose($res);
			throw new PageFlowException("Saving update file to $pathUpdate failed. Nothing was changed");
		}
		fclose($res);
		
		return [];
	}
}