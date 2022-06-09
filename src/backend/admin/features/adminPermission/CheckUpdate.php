<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\Output;

class CheckUpdate extends HasAdminPermission {
	
	private function getVersionNumber($versionString): int {
		$match = preg_match("/(\d+)\.(\d+)\.(\d+)/", $versionString, $matches);
		
		return $match && count($matches) == 4 ? ((int) ($matches[1] .$matches[2] .$matches[3])) : 0;
	}
	
	function exec() {
		$currentVersion = $this->getVersionNumber($_GET['version']);
		$preRelease = (bool) $_GET['preRelease'];
		$branch = $preRelease ? 'develop' : 'main';
		
		$urlPackageInfo = sprintf(Configs::get('url_update_packageInfo'), $branch);
		$json = file_get_contents($urlPackageInfo);
		$versionString = json_decode($json)->version;
		
		
		$versionNumber = $this->getVersionNumber($versionString);
		
		if($currentVersion < $versionNumber) {
			$urlChangelog = sprintf(Configs::get('url_update_changelog'), $branch);
			$changelog=file_get_contents($urlChangelog);
			Output::successObj(['has_update' => true, 'newVersion' => $versionString, 'changelog' => $changelog]);
		}
		else
			Output::successObj(['has_update' => false]);
	}
}