<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\exceptions\CriticalException;
use backend\Paths;

class UpdateStepDownload extends HasAdminPermission {
	
	function exec(): array {
		if(!isset($_POST['url']))
			throw new CriticalException('Missing data');
		
		$url = $_POST['url'];
		
		$pathUpdate = Paths::FILE_SERVER_UPDATE;
		if(file_exists($pathUpdate)) {
			unlink($pathUpdate);
		}
		
		$res = fopen($url, 'r');
		if(!$res) {
			throw new CriticalException("Downloading update from $url failed. Nothing was changed");
		}
		
		if(!file_put_contents($pathUpdate, $res)) {
			fclose($res);
			throw new CriticalException("Saving update file to $pathUpdate failed. Nothing was changed");
		}
		fclose($res);
		
		return [];
	}
}