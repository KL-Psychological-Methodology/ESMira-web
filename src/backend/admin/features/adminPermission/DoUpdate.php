<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\BackupManager;
use backend\exceptions\CriticalException;
use backend\Paths;
use backend\FileSystemBasics;
use backend\exceptions\PageFlowException;
use Throwable;
use ZipArchive;

class DoUpdate extends HasAdminPermission {
	/**
	 * @var string
	 */
	protected $folderPathSource;
	/**
	 * @var string
	 */
	protected $fileUpdate;
	
	private $backupManager;
	
	public function __construct(
		string $folderPathSource = DIR_BASE,
		string $folderPathBackup = Paths::FOLDER_SERVER_BACKUP,
		string $fileUpdate = Paths::FILE_SERVER_UPDATE
	) {
		parent::__construct();
		//we define them here so we can change the location for testing:
		$this->folderPathSource = $folderPathSource;
		$this->fileUpdate = $fileUpdate;
		$this->backupManager = new BackupManager($folderPathSource, $folderPathBackup);
	}
	
	/**
	 * @throws CriticalException
	 * @throws PageFlowException
	 */
	protected function revertUpdate($msg): PageFlowException {
		if(file_exists($this->fileUpdate))
			unlink($this->fileUpdate);
		
		return $this->backupManager->revertFromBackup($msg);
	}
	
	function exec(): array {
		if(!file_exists($this->fileUpdate))
			throw new PageFlowException('Could not find update. Has it been downloaded yet?');
		if($this->backupManager->backupExists())
			throw new PageFlowException("A backup seems to already exist");
		
		try{
			$this->backupManager->moveEverythingToBackupLocation(false);
		} catch(string $m) {
			throw $this->revertUpdate($m);
		}
		
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
			if(!$this->backupManager->restoreConfig())
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