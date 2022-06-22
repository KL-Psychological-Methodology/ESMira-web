<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;

class CheckUpdate extends HasAdminPermission {
	
	protected function getVersionNumber($versionString): int {
		$match = preg_match("/(\d+)\.(\d+)\.(\d+)/", $versionString, $matches);
		
		return $match && count($matches) == 4 ? ((int) ($matches[1] .$matches[2] .$matches[3])) : 0;
	}
	
	function exec(): array {
		$currentVersion = $this->getVersionNumber($_GET['version']);
		$preRelease = (bool) $_GET['preRelease'];
		$branch = $preRelease ? 'develop' : 'main';
		
		$urlPackageInfo = sprintf(Configs::get('url_update_packageInfo'), $branch);
		$json = @file_get_contents($urlPackageInfo);
		if(!$json)
			return ['has_update' => false, 'no_connection' => true];
		$packageInfo = @json_decode($json);
		
		if(!$packageInfo)
			return ['has_update' => false, 'no_connection' => true];
		$versionString = $packageInfo->version;
		
		$versionNumber = $this->getVersionNumber($versionString);
		
		if($currentVersion < $versionNumber) {
			$urlChangelog = sprintf(Configs::get('url_update_changelog'), $branch);
			$changelog=file_get_contents($urlChangelog);
			return ['has_update' => true, 'newVersion' => $versionString, 'changelog' => $changelog];
		}
		else
			return ['has_update' => false];
	}
}