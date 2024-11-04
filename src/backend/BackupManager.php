<?php

namespace backend;

use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;

class BackupManager {
    
    const NEEDS_BACKUP = ['api', 'backend',  'frontend', 'locales', '.htaccess', 'CHANGELOG.md', 'index.php', 'index_nojs.php', 'LICENSE', 'README.md'];
    
    /**
     * @var string
     */
    protected $folderPathSource;

    /**
     * @var string
     */
    protected $folderPathBackup;

    private $filesToRetain = [];
	private $dataBackupStore = null;

    public function __construct(
        string $folderPathSource = DIR_BASE,
        string $folderPathBackup = Paths::FOLDER_SERVER_BACKUP
    )
    {
        $this->folderPathSource = $folderPathSource;
        $this->folderPathBackup = $folderPathBackup;
    }

	/**
	 * Windows throws some weird permission denied exceptions if we try to move the api-folder (probably because it is "used" by the server. So we move the files one by one.
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
	private function move(string $oldLocation, string $newLocation) {
		if(is_file($oldLocation)) {
			if(!rename($oldLocation, $newLocation))
				throw new PageFlowException("Renaming $oldLocation to $newLocation failed.");
		}
		else {
			$handle = opendir($oldLocation);
			while($file = readdir($handle)) {
				if($file == '.' || $file == '..')
					continue;
				
				if(!file_exists($newLocation))
					mkdir($newLocation, 0744);
				if(!@$this->move("$oldLocation/$file", "$newLocation/$file"))
					throw new PageFlowException("Moving $oldLocation to $newLocation failed.");
			}
			closedir($handle);
		}
	}

    /**
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
	public function moveEverythingToBackupLocation(bool $includeData) {
		if(!file_exists($this->folderPathBackup))
			FileSystemBasics::createFolder($this->folderPathBackup);
		
		foreach(self::NEEDS_BACKUP as $file) {
			$oldLocation = $this->folderPathSource .$file;
			$newLocation = $this->folderPathBackup .$file;
			
			if(!file_exists($oldLocation))
				continue;
			if(file_exists($newLocation))
				throw "Critical error! $newLocation already exists. This should never happen. Please check the file structure manually";
			
			
			$this->move($oldLocation, $newLocation);
		}

		if($includeData) {
			$this->dataBackupStore = Configs::getDataStore()->getDataBackupStore();
			$this->dataBackupStore->backupData();
		}
		
		//remember non-ESMira files we want to keep in case we need to revert
		$handle = opendir($this->folderPathSource);
		while($file = readdir($handle)) {
			$this->filesToRetain[] = $file;
		}
		closedir($handle);
	}

    /**
	 * @throws CriticalException
	 * @throws PageFlowException
	 */
	public function revertFromBackup($msg): PageFlowException {
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
			$stringMsg = 'Reverting from Backup failed! The following files are still in the backup folder: ' .implode(',', $revertFailedList) ."\nReverting was caused by this error: \n$msg";
			error_log($stringMsg);
			throw new PageFlowException($stringMsg);
		}
		else
			rmdir($this->folderPathBackup);
		
		if($this->dataBackupStore != null)
			$this->dataBackupStore->revertFromBackup();

		return new PageFlowException($msg);
	}

    public function backupExists(): bool {
        return file_exists($this->folderPathBackup);
    }

    public function deleteBackup() {
		unlink($this->folderPathBackup);
		if($this->dataBackupStore != null) {
			$this->dataBackupStore->deleteBackup();
		}
	}

    public function restoreConfig(): bool {
        return @copy($this->folderPathBackup . Paths::SUB_PATH_CONFIG, Paths::FILE_CONFIG);
    }
}


