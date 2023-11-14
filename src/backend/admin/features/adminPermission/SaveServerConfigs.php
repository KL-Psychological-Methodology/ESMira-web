<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Main;
use backend\Configs;
use backend\FileSystemBasics;
use backend\exceptions\PageFlowException;
use stdClass;

class SaveServerConfigs extends HasAdminPermission {
	/**
	 * @throws PageFlowException
	 */
	private function extractServerName(array $obj, string $langCode): string {
		$serverName = urldecode($obj['serverName'] ?? '');
		if(!Main::strictCheckInput($serverName))
			throw new PageFlowException("The server name '$serverName' in the language '$langCode' has forbidden characters");
		else
			return $serverName;
	}
	function exec(): array {
		if(!($settingsCollection = json_decode(Main::getRawPostInput(), true)))
			throw new PageFlowException('Unexpected data');
		
		if(!isset($settingsCollection['translationData']))
			throw new PageFlowException('Missing data');
		
		$serverStore = Configs::getDataStore()->getServerStore();
		$oldLangCodes = Configs::get('langCodes');
		
		
		
		$serverNames = [];
		foreach($settingsCollection['translationData'] as $code => $obj) {
			if(($k = array_search($code, $oldLangCodes)) !== false)
				unset($oldLangCodes[$k]);
			
			$serverNames[$code] = $this->extractServerName($obj, $code);
			
			$impressum = urldecode($obj['impressum'] ?? '');
			if(strlen($impressum))
				$serverStore->saveImpressum($impressum, $code);
			else
				$serverStore->deleteImpressum($code);
			
			$privacyPolicy = urldecode($obj['privacyPolicy'] ?? '');
			if(strlen($privacyPolicy))
				$serverStore->savePrivacyPolicy($privacyPolicy, $code);
			else
				$serverStore->deletePrivacyPolicy($code);
			
			$homeMessage = urldecode($obj['homeMessage'] ?? '');
			if(strlen($homeMessage))
				$serverStore->saveHomeMessage($homeMessage, $code);
			else
				$serverStore->deleteHomeMessage($code);
		}
		
		$settingsCollection['serverName'] = $serverNames;
		unset($settingsCollection['translationData']);
		FileSystemBasics::writeServerConfigs($settingsCollection);
		
		//if a language has been removed, we need to remove its files too:
		foreach($oldLangCodes as $code) {
			$serverStore->deleteImpressum($code);
			$serverStore->deletePrivacyPolicy($code);
		}
		
		$config = new GetServerConfig();
		return $config->exec();
	}
}