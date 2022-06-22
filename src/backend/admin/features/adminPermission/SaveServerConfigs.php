<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Main;
use backend\Configs;
use backend\FileSystemBasics;
use backend\PageFlowException;
use stdClass;

class SaveServerConfigs extends HasAdminPermission {
	/**
	 * @throws PageFlowException
	 */
	private function extractServerName(stdClass $obj): string {
		$serverName = urldecode($obj->serverName);
		$len = strlen($serverName);
		if($len < 3 || $len > 30)
			throw new PageFlowException("The server name '$serverName' needs to consist of 3 to 30 characters");
		else if(!Main::strictCheckInput($serverName))
			throw new PageFlowException("The server name '$serverName' has forbidden characters");
		else
			return $serverName;
	}
	function exec(): array {
		$serverStore = Configs::getDataStore()->getServerStore();
		
		if(!($settingsCollection = json_decode(Main::getRawPostInput())))
			throw new PageFlowException('Unexpected data');
		
		if(!isset($settingsCollection->_))
			throw new PageFlowException('No default settings');
		
		$oldLangCodes = Configs::get('langCodes');
		
		$serverNames = [];
		$langCodes = [];
		foreach($settingsCollection as $code => $obj) {
			if($code !== '_') {
				array_push($langCodes, $code);
				if(($k = array_search($code, $oldLangCodes)) !== false)
					unset($oldLangCodes[$k]);
			}
			
			$serverNames[$code] = $this->extractServerName($obj);
			
			$impressum = urldecode($obj->impressum);
			if(strlen($impressum))
				$serverStore->saveImpressum($impressum, $code);
			else
				$serverStore->deleteImpressum($code);
			
			$privacyPolicy = urldecode($obj->privacyPolicy);
			if(strlen($privacyPolicy))
				$serverStore->savePrivacyPolicy($privacyPolicy, $code);
			else
				$serverStore->deletePrivacyPolicy($code);
		}
		
		//if a language has been removed, we need to remove its files too:
		foreach($oldLangCodes as $code) {
			$serverStore->deleteImpressum($code);
			$serverStore->deletePrivacyPolicy($code);
		}
		
		FileSystemBasics::writeServerConfigs([
			'serverName' => $serverNames,
			'langCodes' => $langCodes,
		]);
		
		return [];
	}
}