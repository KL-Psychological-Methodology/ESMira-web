<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\Files;
use backend\Output;

class DownloadUpdate extends HasAdminPermission {
	
	function exec() {
		$file_update = Files::get_file_serverUpdate();
		if(file_exists($file_update))
			unlink($file_update);
		$res = fopen(Configs::get('url_update_releaseZip'), 'r');
		if(!$res)
			Output::error('Downloading update failed. Nothing was changed');
		
		if(!file_put_contents($file_update, $res))
			Output::error('Saving update file failed. Nothing was changed');
		
		Output::successObj();
	}
}