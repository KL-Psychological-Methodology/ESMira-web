<?php

namespace backend\fileSystem;

use backend\Configs;
use backend\CriticalError;
use backend\ESMiraInitializer;
use backend\Paths;
use backend\fileSystem\PathsFS;
use backend\fileSystem\loader\PermissionsLoader;
use backend\fileSystem\loader\StudyAccessKeyIndexLoader;
use backend\FileSystemBasics;

class ESMiraInitializerFS implements ESMiraInitializer {
	/**
	 * @throws CriticalError
	 */
	private function assembleDataFolderPath(string $dataLocation): string {
		$last_char = substr($dataLocation, -1);
		if($last_char !== '/' && $last_char !== '\\')
			$dataLocation .= '/';
		
		if(!file_exists($dataLocation))
			throw new CriticalError("The path $dataLocation does not exist on the server");
		
		return $dataLocation .PathsFS::FILENAME_DATA .'/';
	}
	
	/**
	 * @throws CriticalError
	 */
	private function moveExistingDataFolder($pathDataFolder) {
		$reuseFolder = isset($_POST['reuseFolder']) && $_POST['reuseFolder'];
		
		if(file_exists($pathDataFolder) && $reuseFolder) {
			$count = 2;
			
			do {
				$newPath = substr($pathDataFolder, 0, -1) .$count;
				
				if(++$count > 100)
					throw new CriticalError('Too many copies of ' . Paths::FILE_CONFIG .' exist');
			}
			while(file_exists($newPath));
			
			rename($pathDataFolder, $newPath);
		}
	}
	
	/**
	 * @throws CriticalError
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
		$pathDataFolder = Configs::get('dataFolder_path');
		
		$this->moveExistingDataFolder($pathDataFolder);
		$this->createDataFolder($pathDataFolder);
		
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
		return [
			'dir_base' => DIR_BASE,
			'dataFolder_exists' => file_exists($this::assembleDataFolderPath($dataFolderBase))
		];
	}
}