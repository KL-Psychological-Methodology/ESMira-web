<?php

use backend\admin\features\adminPermission\UpdateVersion;

/**
 * @deprecated
 * this is legacy code to fix update from version < 1.5.1
 */
class LegacyUpdateVersion extends UpdateVersion {
	public function __construct() {
		//do nothing because parent::__construct would crash
	}
}


/**
 * @deprecated
 * this is legacy code to fix update from version < 1.5.1
 */
function run_updateScript(string $fromVersion) {
	try {
		$updater = new LegacyUpdateVersion();
		$updater->exec();
	}
	catch(Throwable $e) {
		throw new \Exception("Error while running update script", $e);
	}
}