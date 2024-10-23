<?php

namespace backend\admin\features\adminPermission;

use backend\exceptions\CriticalException;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\exceptions\PageFlowException;
use backend\MigrationManager;
use Throwable;

class UpdateVersion extends DoUpdate {
	/**
	 * @throws CriticalException
	 * @throws PageFlowException
	 */
	function exec(): array {
		$dataVersionPath = PathsFS::fileDataVersion();
		$fromVersion = file_exists($dataVersionPath) ? file_get_contents($dataVersionPath) : $_GET['fromVersion'];
		
		if(!$fromVersion)
			throw new PageFlowException('Missing data');
		
		try {
			$migrationManager = new MigrationManager($fromVersion);
			$migrationManager->run();
		}
		catch(Throwable $e) {
			throw $this->revertUpdate("Error while running update script. Reverting... \n$e");
		}
			
		//cleaning up
		if(file_exists($this->folderPathBackup) && (!FileSystemBasics::emptyFolder($this->folderPathBackup) || !@rmdir($this->folderPathBackup)))
			throw new PageFlowException("Failed to clean up backup. The update was successful. But please delete this folder and check its contents manually: $this->folderPathBackup");
		return [];
	}
}