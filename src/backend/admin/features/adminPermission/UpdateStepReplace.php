<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\exceptions\CriticalException;
use backend\FileSystemBasics;
use backend\Paths;
use backend\exceptions\PageFlowException;
use Throwable;

class UpdateStepReplace extends HasAdminPermission {
	/**
	 * Only exist for testing.
	 */
	protected string $pathStructureFile;
	
	/**
	 * Only exist for testing.
	 */
	protected string $pathHome;
	
	/**
	 * Only exist for testing.
	 */
	protected string $pathUpdate;
	
	/**
	 * Only exist for testing.
	 */
	protected string $pathBackup;
	
	/**
	 * Constructor is only needed for testing.
	 * @param string $pathStructureFile Path to the config file.
	 * @param string $pathUpdate Path to the zipped update file.
	 * @param string $pathBackup Path to the folder where the update should be extracted to.
	 * @throws CriticalException
	 * @throws PageFlowException
	 */
	public function __construct(string $pathStructureFile = Paths::FILE_STRUCTURE, string $pathHome = DIR_BASE, string $pathUpdate = Paths::FOLDER_SERVER_UPDATE, string $pathBackup = Paths::FOLDER_SERVER_BACKUP) {
		parent::__construct();
		$this->pathStructureFile = $pathStructureFile;
		$this->pathHome = $pathHome;
		$this->pathUpdate = $pathUpdate;
		$this->pathBackup = $pathBackup;
	}
	
	/**
	 * Windows sometimes throws some weird permission denied exceptions if we try to move the api-folder (probably because it is "used" by the server). So we move the files one by one.
	 * @throws CriticalException
	 */
	private function move(string $oldLocation, string $newLocation, $replaceExisting = false) {
		if(is_file($oldLocation)) {
			if(file_exists($newLocation)) {
				if($replaceExisting) {
					unlink($newLocation);
				}
				else {
					throw new CriticalException("$newLocation already exists! Cannot move $oldLocation");
				}
			}
			if(!rename($oldLocation, $newLocation)) {
				throw new CriticalException("Renaming $oldLocation to $newLocation failed");
			}
		}
		else {
			if(!file_exists($newLocation)) {
				mkdir($newLocation, 0744);
			}
			$handle = opendir($oldLocation);
			while($file = readdir($handle)) {
				if($file == '.' || $file == '..') {
					continue;
				}
				
				$this->move("$oldLocation/$file", "$newLocation/$file", $replaceExisting);
			}
			closedir($handle);
			rmdir($oldLocation);
		}
	}
	
	function exec(): array {
		if(!file_exists($this->pathUpdate)) {
			throw new CriticalException("Could not find update at $this->pathUpdate");
		}
		if(file_exists($this->pathBackup)) {
			throw new CriticalException("$this->pathBackup already exists!");
		}
		
		// Move existing files to backup:
		try {
			FileSystemBasics::createFolder($this->pathBackup);
			$structure = json_decode(file_get_contents($this->pathStructureFile));
			
			foreach($structure as $file) {
				$path = $this->pathHome . $file;
				if(!file_exists($path)) {
					throw new CriticalException("$path does not exist, but it should!");
				}
				
				$this->move($path, $this->pathBackup . $file);
			}
		}
		catch(Throwable $error) {
			if(file_exists($this->pathBackup)) {
				try {
					$this->move($this->pathBackup, $this->pathHome);
					
					//Remove update folder so it can be redownloaded:
					FileSystemBasics::emptyFolder($this->pathUpdate);
					rmdir($this->pathUpdate);
				}
				catch(Throwable $error2) {
					throw new CriticalException("Something went horribly wrong when moving files to backup! While trying to recover, the following error happened:\n" . $error2->getMessage() . "\n. The original error:\n" . $error->getMessage() . "\nYou might be able to recover manually, by moving the remaining files from ./backup to ./");
				}
			}
			throw new CriticalException("Could not move ESMira files to backup location. The original files have been restored. Error:\n" . $error->getMessage());
		}
		
		
		// Move update into main structure:
		try {
			$this->move($this->pathUpdate, $this->pathHome);
			
			if(function_exists('opcache_reset')) {//for servers using Zend bytecode cache
				opcache_reset();
			}
		}
		catch(Throwable $error) {
			try {
				if(file_exists($this->pathStructureFile)) { // if it exists, we can delete all update files, if not, we have to leave them or we would risk deleting non-ESMira files
					$structure = json_decode(file_get_contents($this->pathStructureFile)); //this should be the structure file from the update
					
					foreach($structure as $file) {
						$path = $this->pathHome . $file;
						if(!file_exists($path)) {
							continue;
						}
						
						unlink($path);
					}
				}
				
				$this->move($this->pathBackup, $this->pathHome, true);
				
				//Remove update folder so it can be redownloaded:
				FileSystemBasics::emptyFolder($this->pathUpdate);
				rmdir($this->pathUpdate);
			}
			catch(Throwable $error2) {
				throw new CriticalException("Something went horribly wrong when updating files! While trying to recover, the following error happened:\n" . $error2->getMessage() . "\n. The original error:\n" . $error->getMessage() . "\nYou might be able to recover manually, by moving the remaining files from ./backup to ./");
			}
			throw new CriticalException("Could not move update. The original files have been restored. Error:\n" . $error->getMessage());
		}
		
		return [];
	}
}