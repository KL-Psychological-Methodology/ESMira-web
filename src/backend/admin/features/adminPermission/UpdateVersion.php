<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\MigrationManager;
use Throwable;

/**
 * Call order:
 * UpdateStepPrepare -> UpdateStepReplace -> UpdateVersion
 */
class UpdateVersion extends HasAdminPermission {
	/**
	 * @throws CriticalException
	 */
	function exec(): array {
		try {
			$dataVersionPath = PathsFS::fileDataVersion();
			$fromVersion = file_exists($dataVersionPath) ? file_get_contents($dataVersionPath) : $_GET['fromVersion'];
			
			if(!$fromVersion) {
				throw new CriticalException('Could not determine from which version to migrate from (backup folder might be missing).');
			}
			FileSystemBasics::writeServerConfigs([]); //copies values over from the new default configs
			
			$migrationManager = new MigrationManager($fromVersion);
			$migrationManager->run();
		}
		catch(Throwable $e) {
			throw new CriticalException("The update finished successfully but failed when migrating the data to the current version. Please copy this error message and your server logs and ask for assistance at https://github.com/KL-Psychological-Methodology/ESMira/issues\n$e");
		}
		finally {
			Configs::getDataStore()->setMaintenanceMode(false);
		}
		
		return [];
	}
	
	protected function isReady(): bool {
		return true;
	}
}