<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\exceptions\CriticalException;
use backend\Paths;
use backend\FileSystemBasics;
use backend\exceptions\PageFlowException;
use ZipArchive;

class DoUpdate extends HasAdminPermission {
	//we dont want to blindly copy everything over in case there are non ESMira-files in our main folder:
	const NEEDS_BACKUP = ['api/', 'backend/', 'frontend/', 'locales/', '.htaccess', 'CHANGELOG.md', 'index.php', 'index_nojs.php', 'LICENSE', 'README.md'];
	
	/**
	 * @var string
	 */
	protected $folderPathSource;
	/**
	 * @var string
	 */
	protected $folderPathBackup;
	/**
	 * @var string
	 */
	protected $fileUpdate;
	
	private $filesToRetain = [];
	
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
	 * @throws CriticalException
	 * @throws PageFlowException
	 */
	protected function revertUpdate($msg): PageFlowException {
		if(file_exists($this->fileUpdate))
			unlink($this->fileUpdate);
		
		//source contains the files from the update. So remove them first
		$handle = opendir($this->folderPathSource);
		while($file = readdir($handle)) {
			if(in_array($file, $this->filesToRetain))
				continue;
			
			$path = $this->folderPathSource .$file;
			if(is_file($path))
				unlink($path);
			else {
				FileSystemBasics::emptyFolder($path);
				rmdir($path);
			}
		}
		closedir($handle);
		
		$revertFailedList = [];
		
		//now, copy everything back from the backup folder:
		if(file_exists($this->folderPathBackup)) {
			$handle = opendir($this->folderPathBackup);
			while($file = readdir($handle)) {
				if($file[0] != '.') {
					$oldLocation = $this->folderPathBackup . $file;
					$newLocation = $this->folderPathSource . $file;
					
					if(file_exists($newLocation) || !@rename($oldLocation, $newLocation))
						$revertFailedList[] = $newLocation;
				}
			}
			closedir($handle);
		}
		
		if(count($revertFailedList))
			throw new PageFlowException("Reverting update failed! The following files are still in the backup folder: $this->folderPathBackup\nReverting was caused by this error: \n$msg");
		else
			rmdir($this->folderPathBackup);
		
		return new PageFlowException($msg);
	}
	
	/**
	 * @throws PageFlowException
	 * @throws CriticalException
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
		
		//remember non-ESMira files we want to keep in case we need to revert
		$handle = opendir($this->folderPathSource);
		while($file = readdir($handle)) {
			$this->filesToRetain[] = $file;
		}
		closedir($handle);
	}
	
	function exec(): array {
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
		@unlink($this->fileUpdate);
		
		//restore config file:
		if(!@copy($this->folderPathBackup .Paths::SUB_PATH_CONFIG, Paths::FILE_CONFIG))
			throw $this->revertUpdate('Could not restore settings. Reverting...');
		FileSystemBasics::writeServerConfigs([]); //copies values over from the new default configs
		
		return [];
	}
}