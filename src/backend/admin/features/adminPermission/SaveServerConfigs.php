<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Base;
use backend\Configs;
use backend\Files;
use backend\Output;

class SaveServerConfigs extends HasAdminPermission {
	
	function exec() {
		$settingsCollection_json = file_get_contents('php://input');
		
		if(!($settingsCollection = json_decode($settingsCollection_json)))
			Output::error('Unexpected data');
		
		if(!isset($settingsCollection->_))
			Output::error('No default settings');
		
		$old_langCodes = Configs::get('langCodes');
		
		$serverNames = [];
		$langCodes = [];
		foreach($settingsCollection as $code => $s) {
			if($code !== '_') {
				array_push($langCodes, $code);
				if (($k = array_search($code, $old_langCodes)) !== false)
					unset($old_langCodes[$k]);
			}
			$serverName = urldecode($s->serverName);
			$impressum = urldecode($s->impressum);
			$privacyPolicy = urldecode($s->privacyPolicy);
			
			$len = strlen($serverName);
			if($len < 3 || $len > 30)
				Output::error('The server name needs to consist of 3 and 30 characters');
			else if(!Base::check_input($serverName))
				Output::error('The server name has forbidden characters');
			else
				$serverNames[$code] = $serverName;
			
			$file_impressum = Files::get_file_langImpressum($code);
			if(strlen($impressum))
				$this->write_file($file_impressum, $impressum);
			else if(file_exists($file_impressum))
				unlink($file_impressum);
			
			$file_privacyPolicy = Files::get_file_langPrivacyPolicy($code);
			if(strlen($privacyPolicy))
				$this->write_file($file_privacyPolicy, $privacyPolicy);
			else if(file_exists($file_privacyPolicy))
				unlink($file_privacyPolicy);
		}
		
		//if a language has been removed, we need to remove its files too:
		foreach($old_langCodes as $code) {
			$file_impressum = Files::get_file_langImpressum($code);
			if(file_exists($file_impressum))
				unlink($file_impressum);
			
			$file_privacyPolicy = Files::get_file_langPrivacyPolicy($code);
			if(file_exists($file_privacyPolicy))
				unlink($file_privacyPolicy);
		}
		
		$this->write_serverConfigs([
			'serverName' => $serverNames,
			'langCodes' => $langCodes,
		]);
		
		Output::successObj();
	}
}