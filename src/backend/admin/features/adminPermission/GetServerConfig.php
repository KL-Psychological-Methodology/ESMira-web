<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\Files;
use backend\Output;

class GetServerConfig extends HasAdminPermission {
	
	function exec() {
		$serverSettings = Configs::getAll();
		$serverSettings['impressum'] = [];
		$serverSettings['privacyPolicy'] = [];
		
		$langCodes = Configs::get('langCodes');
		array_push($langCodes, '_');
		foreach($langCodes as $code) {
			$file_impressum = Files::get_file_langImpressum($code);
			if(file_exists($file_impressum))
				$serverSettings['impressum'][$code] = file_get_contents($file_impressum);
			
			$file_privacyPolicy = Files::get_file_langPrivacyPolicy($code);
			if(file_exists($file_privacyPolicy))
				$serverSettings['privacyPolicy'][$code] = file_get_contents($file_privacyPolicy);
		}
		Output::successObj($serverSettings);
	}
}