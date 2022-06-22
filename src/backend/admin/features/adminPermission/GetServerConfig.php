<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;

class GetServerConfig extends HasAdminPermission {
	
	function exec(): array {
		$serverSettings = Configs::getAll();
		$serverSettings['impressum'] = [];
		$serverSettings['privacyPolicy'] = [];
		
		$langCodes = Configs::get('langCodes');
		$langCodes[] = '_';
		$serverStore = Configs::getDataStore()->getServerStore();
		foreach($langCodes as $code) {
			$impressum = $serverStore->getImpressum($code);
			if(!empty($impressum))
				$serverSettings['impressum'][$code] = $impressum;
			
			$privacyPolicy = $serverStore->getPrivacyPolicy($code);
			if(!empty($impressum))
				$serverSettings['privacyPolicy'][$code] = $privacyPolicy;
		}
		return $serverSettings;
	}
}