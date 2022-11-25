<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;

class GetServerConfig extends HasAdminPermission {
	
	function exec(): array {
		$langCodes = Configs::get('langCodes');
		$serverName = Configs::get('serverName');
		$configs = [
			'langCodes' => $langCodes,
			'defaultLang' => Configs::get('defaultLang')
		];
		$translationData = [];
		
		$serverStore = Configs::getDataStore()->getServerStore();
		foreach($langCodes as $code) {
			$translationData[$code] = [
				'serverName' => $serverName[$code] ?? '',
				'impressum' => $serverStore->getImpressum($code),
				'privacyPolicy' => $serverStore->getPrivacyPolicy($code)
			];
		}
		return [
			'configs' => $configs,
			'translationData' => $translationData
		];
	}
}