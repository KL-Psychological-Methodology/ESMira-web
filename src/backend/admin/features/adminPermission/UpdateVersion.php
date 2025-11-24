<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\exceptions\CriticalException;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\MigrationManager;
use backend\Paths;
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
		
		//cleaning up
		$pathBackup = Paths::FOLDER_SERVER_BACKUP;
		if(file_exists($pathBackup) && (!FileSystemBasics::emptyFolder($pathBackup) || !rmdir($pathBackup))) {
			throw new CriticalException("Failed to clean up backup. The update finished successfully. But please delete this folder and its contents manually: $pathBackup");
		}
		return [];
	}
}