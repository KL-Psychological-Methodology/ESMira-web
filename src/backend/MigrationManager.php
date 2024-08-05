<?php

namespace backend;

use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\fileSystem\PathsFS;

class MigrationManager {
	/**
	 * @var string
	 */
	private $fromVersion;
	
	public function __construct(string $fromVersion = '') {
		$this->fromVersion = $fromVersion;
	}
	
	private function versionIsBelowThen(string $newVersionString): bool {
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
	private function runUpdateScript() {
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
	public function run() {
		if(file_exists(Paths::FILE_SERVER_VERSION) && $this->fromVersion == file_get_contents(Paths::FILE_SERVER_VERSION))
			return;
		
		if(function_exists('opcache_reset')) //for servers using Zend bytecode cache
			opcache_reset();
		$this->runUpdateScript();
		
		$dataVersionPath = PathsFS::fileDataVersion();
		if(!@copy(Paths::FILE_SERVER_VERSION, $dataVersionPath))
			throw new PageFlowException("Error while copying version number");
		
	}
	
	/**
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
	static function autoRun() {
		$dataVersionPath = PathsFS::fileDataVersion();
		if(file_exists($dataVersionPath)) {
			$migrationManager = new MigrationManager(file_get_contents($dataVersionPath));
			$migrationManager->run();
		}
	}
	
	//only for testing:
	static function testVersionCheck(string $fromVersion, string $checkVersion): bool {
		$obj = new MigrationManager($fromVersion);
		return $obj->versionIsBelowThen($checkVersion);
	}
}