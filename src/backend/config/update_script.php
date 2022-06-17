<?php

use backend\Configs;

/**
 * @throws Exception
 */
function run_updateScript($fromVersion) {
	if($fromVersion <= 150) {
		$default = Configs::getDefaultAll();
		if(!$this->write_serverConfigs([
			'url_update_packageInfo' => $default['url_update_packageInfo'],
			'url_update_changelog' => $default['url_update_changelog'],
			'url_update_releaseZip' => $default['url_update_releaseZip'],
			'url_update_preReleaseZip' => $default['url_update_preReleaseZip']
		]))
			throw new Exception('Could not update config');
	}
}