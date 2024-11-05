<?php

namespace backend\fileSystem;

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\ESMiraInitializer;
use backend\exceptions\PageFlowException;
use backend\MigrationManager;
use backend\Paths;
use backend\fileSystem\PathsFS;
use backend\fileSystem\loader\PermissionsLoader;
use backend\fileSystem\loader\StudyAccessKeyIndexLoader;
use backend\FileSystemBasics;

class ESMiraInitializerFS implements ESMiraInitializer {
	/**
	 * @throws CriticalException
	 */
	private function assembleDataFolderPath(string $dataLocation): string {
		$last_char = substr($dataLocation, -1);
		if($last_char !== '/' && $last_char !== '\\')
			$dataLocation .= '/';
		
		if(!file_exists($dataLocation))
			throw new CriticalException("The path $dataLocation does not exist on the server");
		
		return $dataLocation .PathsFS::FILENAME_DATA .'/';
	}
	
	/**
	 * @throws CriticalException
	 * @throws PageFlowException
	 */
	private function moveExistingDataFolder($pathDataFolder) {
		if(file_exists($pathDataFolder) && !FileSystemBasics::isDirEmpty($pathDataFolder)) {
			if(isset($_POST['reuseFolder']) && $_POST['reuseFolder'])
				MigrationManager::autoRun();
			else {
				$count = 2;
				
				do {
					$newPath = substr($pathDataFolder, 0, -1) .$count;
					
					if(++$count > 100)
						throw new CriticalException('Too many copies of ' . Paths::FILE_CONFIG .' exist');
				}
				while(file_exists($newPath));
				
				if(!@rename($pathDataFolder, $newPath))
					throw new CriticalException('Could not move existing esmira_data folder');
			}
		}
		
	}
	
	/**
	 * @throws CriticalException
	 */
	private function createDataFolder($pathDataFolder) {
		FileSystemBasics::createFolder($pathDataFolder);
		
		if(!file_exists($pathDataFolder .'.htaccess'))
			FileSystemBasics::writeFile($pathDataFolder .'.htaccess', 'Deny from all');
		
		FileSystemBasics::createFolder(PathsFS::folderErrorReports());
		FileSystemBasics::createFolder(PathsFS::folderLegal());
		FileSystemBasics::createFolder(PathsFS::folderTokenRoot());
		FileSystemBasics::createFolder(PathsFS::folderStudies());
		
		if(!file_exists(PathsFS::fileStudyIndex()))
			StudyAccessKeyIndexLoader::exportFile([]);
	}
	
	
	
	public function create($accountName, $password) {
		//folder will not exist
		FileSystemBasics::createFolder(Paths::FILE_CONFIG);
		$pathDataFolder = Configs::get('dataFolder_path');
		
		$this->moveExistingDataFolder($pathDataFolder);
		$this->createDataFolder($pathDataFolder);
		copy(Paths::FILE_SERVER_VERSION, $pathDataFolder . Paths::FILENAME_VERSION);
		
		//create login:
		Configs::getDataStore()->getAccountStore()->setAccount($accountName, $password);
		
		//create permissions file:
		if(file_exists(PathsFS::filePermissions())) {
			$permissions = PermissionsLoader::importFile();
			
			if(!isset($permissions[$accountName]))
				$permissions[$accountName] = ['admin' => true];
			else
				$permissions[$accountName]['admin'] = true;
		}
		else
			$permissions = [$accountName => ['admin' => true]];
		
		PermissionsLoader::exportFile($permissions);
	}
	
	public function getConfigAdditions(): array {
		return ['dataFolder_path' => $this->assembleDataFolderPath($_POST['data_location'])];
	}
	
	public function getInfoArray(string $dataFolderBase = DIR_BASE): array {
	    $folder = $this::assembleDataFolderPath($dataFolderBase);
		return [
			'dirBase' => DIR_BASE,
			'dataFolderExists' => file_exists($folder) && (new \FilesystemIterator($folder))->valid() //check if dir is empty
		];
	}
}