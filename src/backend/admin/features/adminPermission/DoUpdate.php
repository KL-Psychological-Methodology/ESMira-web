<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Files;
use backend\Output;
use Exception;
use ZipArchive;

class DoUpdate extends HasAdminPermission {
	
	function revertUpdate() {
		$pathBackup = Files::get_folder_serverBackup();
		$pathUpdate = Files::get_file_serverUpdate();
		
		if(file_exists($pathUpdate))
			unlink($pathUpdate);
		
		$handle = opendir($pathBackup);
		while($file = readdir($handle)) {
			if($file[0] != '.') {
				$oldLocation = $pathBackup .$file;
				$newLocation = DIR_BASE .$file;
				
				if(file_exists($newLocation)) {
					if(is_dir($newLocation)) {
						$this->empty_folder($newLocation);
						rmdir($newLocation);
					}
					else
						unlink($newLocation);
				}
				rename($oldLocation, $newLocation);
			}
		}
		closedir($handle);
		
		rmdir($pathBackup);
	}
	
	function exec() {
		$needsBackup = ['api/', 'backend/', 'frontend/', '.htaccess', 'CHANGELOG.md', 'index.php', 'index_nojs.php', 'LICENSE', 'README.md', 'version.txt'];
		$folderPathBackup = Files::get_folder_serverBackup();
		$pathUpdate = Files::get_file_serverUpdate();
		
		if(!file_exists($pathUpdate))
			Output::error('Could not find update. Has it been downloaded yet?');
		
		if(!file_exists($folderPathBackup))
			$this->create_folder($folderPathBackup);
		
		//moving current files to backup location:
		foreach($needsBackup as $file) {
			$oldLocation = DIR_BASE .$file;
			$newLocation = $folderPathBackup .$file;
			
			if(!file_exists($oldLocation))
				continue;
			if(file_exists($newLocation)) {
				if(is_dir($newLocation)) {
					$this->empty_folder($newLocation);
					rmdir($newLocation);
				}
				else
					unlink($newLocation);
			}
			
			if(!rename($oldLocation, $newLocation)) {
				$this->revertUpdate();
				Output::error("Renaming $oldLocation to $newLocation failed. Reverting...");
			}
		}
		
		
		//unpacking update:
		$zip = new ZipArchive;
		if(!$zip->open($pathUpdate)) {
			$this->revertUpdate();
			Output::error("Could not open the the zipped update: $pathUpdate. Reverting...");
		}
		if(!$zip->extractTo(DIR_BASE)) {
			$this->revertUpdate();
			Output::error("Could not unzip update: $pathUpdate. Reverting...");
		}
		$zip->close();
		
		
		//restore config file:
		if(!copy($folderPathBackup .Files::PATH_CONFIG, Files::FILE_CONFIG) || !$this->write_serverConfigs([])) {
			$this->revertUpdate();
			Output::error('Could not restore settings. Reverting...');
		}
		
		//run update script
		if(file_exists(Files::FILE_UPDATE_SCRIPT)) {
			require_once Files::FILE_UPDATE_SCRIPT;
			try {
				run_updateScript((int)$_GET['fromVersion']);
				unlink(Files::FILE_UPDATE_SCRIPT);
			}
			catch(Exception $e) {
				$this->revertUpdate();
				Output::error("Error while running update script. Reverting... \n$e");
			}
		}
		
		
		//cleaning up
		if(!$this->empty_folder($folderPathBackup) || !rmdir($folderPathBackup) || !unlink($pathUpdate))
			Output::error("Cleaning up backup failed. The update was successful. But please delete this folder and its contents manually: $folderPathBackup");
		
		Output::successObj();
	}
}