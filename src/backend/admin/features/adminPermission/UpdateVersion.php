<?php

namespace backend\admin\features\adminPermission;

use backend\exceptions\CriticalException;
use backend\FileSystemBasics;
use backend\exceptions\PageFlowException;
use Throwable;

class UpdateVersion extends DoUpdate {
	/**
	 * @var string
	 */
	private $fromVersion;
	
	protected function versionIsBelowThen(string $newVersionString): bool {
		$oldVersionString = $this->fromVersion;
		$matchOld = preg_match("/(\d+)\.(\d+)\.(\d+)\D*(\d*)/", $oldVersionString, $integersOld);
		$matchNew = preg_match("/(\d+)\.(\d+)\.(\d+)\D*(\d*)/", $newVersionString, $integersNew);
		
		return $matchOld && $matchNew &&
			(
				(int) $integersNew[1] > (int) $integersOld[1] // e.g. 2.0.0 > 1.0.0
				|| (
					$integersNew[1] === $integersOld[1]
					&& (
						(int) $integersNew[2] > (int) $integersOld[2] // e.g. 2.1.0 > 2.0.0
						|| (
							$integersNew[2] === $integersOld[2]
							&& (
								(int) $integersNew[3] > (int) $integersOld[3] // e.g. 2.1.1 > 2.1.0
								|| (
									$integersNew[3] === $integersOld[3]
									&& (
										($integersOld[4] !== '' && $integersNew[4] == '') // e.g. 2.1.1 > 2.1.1-alpha.1
										|| ($integersOld[4] !== '' && $integersNew[4] !== '' && (int) $integersNew[4] > (int) $integersOld[4]) // e.g. 2.1.1-alpha.2 > 2.1.1-alpha.1
									)
								)
							)
						)
					)
				)
			);
	}
	
	/**
	 * @throws CriticalException
	 */
	function runUpdateScript() {
		$handle = opendir(DIR_BASE .'backend/admin/updateScripts');
		while($fileName = readdir($handle)) {
			$match = preg_match("/^(.*)\.php$/", $fileName, $result);
			
			if($match && $this->versionIsBelowThen($result[1]))
				require(DIR_BASE ."backend/admin/updateScripts/$fileName");
		}
		closedir($handle);
	}
	
	/**
	 * @throws CriticalException
	 * @throws PageFlowException
	 */
	function exec(): array {
		if(!isset($_GET['fromVersion']))
			throw new PageFlowException('Missing data');
		
		$this->fromVersion = $_GET['fromVersion'];
		try {
			if(function_exists('opcache_reset')) //for servers using Zend bytecode cache
				opcache_reset();
			$this->runUpdateScript();
		}
		catch(Throwable $e) {
			throw $this->revertUpdate("Error while running update script. Reverting... \n$e");
		}
		
		
		//cleaning up
		if(file_exists($this->folderPathBackup) && (!FileSystemBasics::emptyFolder($this->folderPathBackup) || !@rmdir($this->folderPathBackup)))
			throw new PageFlowException("Failed to clean up backup. The update was successful. But please delete this folder and check its contents manually: $this->folderPathBackup");
		return [];
	}
	
	
	//only for testing:
	function testVersionCheck(string $fromVersion, string $checkVersion): bool {
		$this->fromVersion = $fromVersion;
		return $this->versionIsBelowThen($checkVersion);
	}
}