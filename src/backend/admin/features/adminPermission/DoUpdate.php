<?php

namespace backend\admin\features\adminPermission;

use backend\CriticalError;
use backend\Paths;
use backend\FileSystemBasics;
use backend\PageFlowException;
use Exception;
use ZipArchive;

class DoUpdate extends CheckUpdate {
	//we dont want to blindly copy everything over in case there are non ESMira-files in our main folder:
	const NEEDS_BACKUP = ['api/', 'backend/', 'frontend/', 'locales/', '.htaccess', 'CHANGELOG.md', 'index.php', 'index_nojs.php', 'LICENSE', 'README.md'];
	
	/**
	 * @var string
	 */
	private $folderPathSource;
	/**
	 * @var string
	 */
	private $folderPathBackup;
	/**
	 * @var string
	 */
	private $fileUpdate;
	
	public function __construct(
		string $folderPathSource = DIR_BASE,
		string $folderPathBackup = Paths::FOLDER_SERVER_BACKUP,
		string $fileUpdate = Paths::FILE_SERVER_UPDATE
	) {
		parent::__construct();
		//we define them here so we can change the location for testing:
		$this->folderPathSource = $folderPathSource;
		$this->folderPathBackup = $folderPathBackup;
		$this->fileUpdate = $fileUpdate;
	}
	
	/**
	 * @throws CriticalError
	 * @throws PageFlowException
	 */
	private function revertUpdate($msg): PageFlowException {
		if(file_exists($this->fileUpdate))
			unlink($this->fileUpdate);
		
		FileSystemBasics::emptyFolder($this->folderPathSource); //right now, this contains the file from the update. So remove them first
		
		$revertFailedList = [];
		
		//now, copy everything back from the backup folder:
		$handle = opendir($this->folderPathBackup);
		while($file = readdir($handle)) {
			if($file[0] != '.') {
				$oldLocation = $this->folderPathBackup .$file;
				$newLocation = $this->folderPathSource .$file;
				
				if(file_exists($newLocation) || !@rename($oldLocation, $newLocation))
					$revertFailedList[] = $newLocation;
			}
		}
		closedir($handle);
		
		if(count($revertFailedList))
			throw new PageFlowException("Reverting update failed! The following files are still in the backup folder: $this->folderPathBackup\nReverting was caused by this error: \n$msg");
		else
			rmdir($this->folderPathBackup);
		
		return new PageFlowException($msg);
	}
	
	/**
	 * @throws PageFlowException
	 * @throws CriticalError
	 */
	private function moveEverythingToBackupLocation() {
		if(!file_exists($this->folderPathBackup))
			FileSystemBasics::createFolder($this->folderPathBackup);
		
		foreach(self::NEEDS_BACKUP as $file) {
			$oldLocation = $this->folderPathSource .$file;
			$newLocation = $this->folderPathBackup .$file;
			
			if(!file_exists($oldLocation))
				continue;
			if(file_exists($newLocation))
				throw $this->revertUpdate("Critical error! $newLocation already exists. This should never happen. Please check the file structure manually");
			
			if(!@rename($oldLocation, $newLocation))
				throw $this->revertUpdate("Renaming $oldLocation to $newLocation failed. Reverting...");
		}
	}
	
	function exec(): array {
		if(!isset($_GET['fromVersion']))
			throw new PageFlowException('Missing data');
		if(!file_exists($this->fileUpdate))
			throw new PageFlowException('Could not find update. Has it been downloaded yet?');
		if(file_exists($this->folderPathBackup))
			throw new PageFlowException("A backup seems to already exist at: $this->folderPathBackup");
		
		
		$this->moveEverythingToBackupLocation();
		
		
		//unpacking update:
		$zip = new ZipArchive;
		if(!@$zip->open($this->fileUpdate))
			throw $this->revertUpdate("Could not open the the zipped update: $this->fileUpdate. Reverting...");
		if(!@$zip->extractTo($this->folderPathSource))
			throw $this->revertUpdate("Could not unzip update: $this->fileUpdate. Reverting...");
		$zip->close();
		
		
		//restore config file:
		if(!@copy($this->folderPathBackup .Paths::SUB_PATH_CONFIG, Paths::FILE_CONFIG))
			throw $this->revertUpdate('Could not restore settings. Reverting...');
		FileSystemBasics::writeServerConfigs([]); //copies values over from the new default configs
		
		
		//run update script
		try {
			$updater = new UpdateVersion();
			$updater->exec();
		}
		catch(Exception $e) {
			throw $this->revertUpdate("Error while running update script. Reverting... \n$e");
		}
		
		
		//cleaning up
		if(!FileSystemBasics::emptyFolder($this->folderPathBackup) || !@rmdir($this->folderPathBackup) || !@unlink($this->fileUpdate))
			throw new PageFlowException("Failed to clean up backup. The update was successful. But please delete this folder and check its contents manually: $this->folderPathBackup");
		
		return [];
	}
}