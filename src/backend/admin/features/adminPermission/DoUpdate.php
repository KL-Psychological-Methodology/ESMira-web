<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Files;
use backend\Output;
use ZipArchive;

class DoUpdate extends HasAdminPermission {
	
	function revertUpdate() {
		$folder_backup = Files::get_folder_serverBackup();
		$file_update = Files::get_file_serverUpdate();
		
		if(file_exists($file_update))
			unlink($file_update);
		
		$h_folder = opendir($folder_backup);
		while($file = readdir($h_folder)) {
			if($file[0] != '.') {
				$oldLocation = $folder_backup .$file;
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
		closedir($h_folder);
		
		rmdir($folder_backup);
	}
	
	function exec() {
		$needsBackup = ['api/', 'backend/', 'frontend/', '.htaccess', 'CHANGELOG.md', 'index.php', 'index_nojs.php', 'LICENSE', 'README.md', 'version.txt'];
		$folder_backup = Files::get_folder_serverBackup();
		$file_update = Files::get_file_serverUpdate();
		
		if(!file_exists($file_update))
			Output::error('Could not find update. Has it been downloaded yet?');
		
		if(!file_exists($folder_backup))
			$this->create_folder($folder_backup);
		
		//moving current files to backup location:
		foreach($needsBackup as $file) {
			$oldLocation = DIR_BASE .$file;
			$newLocation = $folder_backup .$file;
			
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
		if(!$zip->open($file_update)) {
			$this->revertUpdate();
			Output::error("Could not open the the zipped update: $file_update. Reverting...");
		}
		if(!$zip->extractTo(DIR_BASE)) {
			$this->revertUpdate();
			Output::error("Could not unzip update: $file_update. Reverting...");
		}
		$zip->close();
		
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
		
		//restore config file:
		if(!rename($folder_backup .Files::PATH_CONFIG, Files::FILE_CONFIG)) {
			$this->revertUpdate();
			Output::error('Could not restore settings. Reverting...');
		}
		
		
		//cleaning up
		if(!$this->empty_folder($folder_backup) || !rmdir($folder_backup))
			Output::error("Cleaning up backup failed. The update was successful. But please delete this folder and its contents manually: $folder_backup");
		
		Output::successObj();
	}
}