<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\exceptions\CriticalException;
use backend\Paths;
use backend\FileSystemBasics;
use backend\exceptions\PageFlowException;
use Throwable;
use ZipArchive;

class DoUpdate extends HasAdminPermission {
	//we dont want to blindly copy everything over in case there are non ESMira-files in our main folder:
	const NEEDS_BACKUP = ['api', 'backend',  'frontend', 'locales', '.htaccess', 'CHANGELOG.md', 'index.php', 'index_nojs.php', 'LICENSE', 'README.md'];
	
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
		
		$revertFailedList = [];
		
		//now, copy everything back from the backup folder:
		if(file_exists($this->folderPathBackup)) {
			$handle = opendir($this->folderPathBackup);
			while($file = readdir($handle)) {
				if($file == '.' || $file == '..')
					continue;
				
				$oldLocation = $this->folderPathBackup . $file;
				$newLocation = $this->folderPathSource . $file;
				
				//source contains the files from the update. So remove them first:
				if(file_exists($newLocation)) {
					if(is_file($newLocation))
						unlink($newLocation);
					else {
						FileSystemBasics::emptyFolder($newLocation);
						rmdir($newLocation);
					}
				}
				
				//Now we move stuff back:
				if(!@rename($oldLocation, $newLocation))
					$revertFailedList[] = $newLocation;
			}
			closedir($handle);
		}
		
		if(count($revertFailedList)) {
			$stringMsg = 'Reverting update failed! The following files are still in the backup folder: ' .implode(',', $revertFailedList) ."\nReverting was caused by this error: \n$msg";
			error_log($stringMsg);
			throw new PageFlowException($stringMsg);
		}
		else
			rmdir($this->folderPathBackup);
		
		return new PageFlowException($msg);
	}
	
	
	/**
	 * Windows throws some weird permission denied exceptions if we try to move the api-folder (probably because it is "used" by the server. So we move the files one by one.
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
	private function move(string $oldLocation, string $newLocation) {
		if(is_file($oldLocation)) {
			if(!rename($oldLocation, $newLocation))
				throw $this->revertUpdate("Renaming $oldLocation to $newLocation failed. Reverting...");
		}
		else {
			$handle = opendir($oldLocation);
			while($file = readdir($handle)) {
				if($file == '.' || $file == '..')
					continue;
				
				if(!file_exists($newLocation))
					mkdir($newLocation, 0744);
				$this->move("$oldLocation/$file", "$newLocation/$file");
			}
			closedir($handle);
		}
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
			
			
			$this->move($oldLocation, $newLocation);
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
		
		try {
			//unpacking update:
			$zip = new ZipArchive;
			if(!@$zip->open($this->fileUpdate))
				throw $this->revertUpdate("Could not open the the zipped update: $this->fileUpdate. Reverting...");
			if(!@$zip->extractTo($this->folderPathSource))
				throw $this->revertUpdate("Could not unzip update: $this->fileUpdate. Reverting...");
			$zip->close();
			@unlink($this->fileUpdate);
			
			//restore config file:
			if(!@copy($this->folderPathBackup . Paths::SUB_PATH_CONFIG, Paths::FILE_CONFIG))
				throw $this->revertUpdate('Could not restore settings. Reverting...');
			FileSystemBasics::writeServerConfigs([]); //copies values over from the new default configs
			if(function_exists('opcache_reset')) //for servers using Zend bytecode cache
				opcache_reset();
		}
		catch(Throwable $e) {
			throw $this->revertUpdate($e->getMessage());
		}
		return [];
	}
}