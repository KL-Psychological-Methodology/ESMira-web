<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\FileSystemBasics;
use backend\Paths;
use backend\exceptions\PageFlowException;
use backend\SSE;
use Throwable;

/**
 * Call order:
 * UpdateStepPrepare -> UpdateStepReplace -> UpdateVersion
 */
class UpdateStepReplace extends HasAdminPermission {
	private const MAX_STAGES = 2;
	
	protected string $pathStructureFile;
	protected string $pathOriginal;
	protected string $pathUpdate;
	protected string $pathBackup;
	private SSE $sse;
	
	
	/**
	 * Constructor is only needed for testing.
	 * @param string $pathStructureFile Path to the STRUCTURE file (only used for testing).
	 * @param string $pathOriginal Path to DIR_BASE (only used for testing).
	 * @param string $pathUpdate Path to the folder with the update files (only used for testing).
	 * @param string $pathBackup Path to the temporary folder where existing files should be moved to (only used for testing).
	 * @param SSE|null $sse SSE object to use for sending progress updates (only used for testing).
	 * @throws CriticalException
	 * @throws PageFlowException
	 */
	public function __construct(
		string $pathStructureFile = Paths::FILE_STRUCTURE,
		string $pathOriginal = DIR_BASE,
		string $pathUpdate = Paths::FOLDER_SERVER_UPDATE,
		string $pathBackup = Paths::FOLDER_SERVER_BACKUP,
		?SSE $sse = null // only used for testing
	) {
		parent::__construct();
		$this->pathStructureFile = $pathStructureFile;
		$this->pathOriginal = $pathOriginal;
		$this->pathUpdate = $pathUpdate;
		$this->pathBackup = $pathBackup;
		$this->sse = $sse ?? new SSE();
	}
	
	private function getStructureFileList(): array {
		return json_decode(file_get_contents($this->pathStructureFile));
	}
	
	private function flushProgress(int $stage, int $step, int $total): void {
		$this->sse->flushProgress($stage, self::MAX_STAGES, $step, $total);
	}
	
	/**
	 * @throws CriticalException
	 */
	private function revert() {
		$pathServerBackup = $this->pathBackup .Paths::SUB_PATH_SERVER_UPDATE_FILES;
		FileSystemBasics::moveOneByOne($pathServerBackup, $this->pathOriginal, true, function(int $step, int $total) {
			$this->flushProgress(1, $total - $step, $total);
		});
		
		//Cleanup:
		FileSystemBasics::emptyFolder($this->pathUpdate);
		rmdir($this->pathUpdate);
		
		if(file_exists($pathServerBackup) && (!FileSystemBasics::emptyFolder($pathServerBackup) || !rmdir($pathServerBackup))) {
			throw new CriticalException('Failed to clean up backup');
		}
	}
	
	function execAndOutput() {
		$dataStore = Configs::getDataStore();
		try {
			$dataStore->setMaintenanceMode(true);
			$pathServerUpdate = $this->pathUpdate . Paths::SUB_PATH_SERVER_UPDATE_FILES;
			$pathServerBackup = $this->pathBackup . Paths::SUB_PATH_SERVER_UPDATE_FILES;
			
			try {
				$this->sse->sendHeader();
				
				if(!file_exists($pathServerUpdate)) {
					throw new CriticalException("Could not find update at $pathServerUpdate");
				}
				if(file_exists($pathServerBackup)) {
					throw new CriticalException("$pathServerBackup already exists!");
				}
				if(!file_exists($this->pathStructureFile)) {
					throw new CriticalException("$this->pathStructureFile does not exist!");
				}
			}
			catch(Throwable $error) {
				if(file_exists($this->pathUpdate)) {
					FileSystemBasics::emptyFolder($this->pathUpdate);
				}
				throw $error;
			}
			
			// Move existing files to backup:
			try {
				$this->flushProgress(1, 0, 1);
				FileSystemBasics::createFolder($pathServerBackup, true);
				$structure = $this->getStructureFileList();
				$needBackupTotal = count($structure);
				$step = 0;
				
				foreach($structure as $file) {
					$path = $this->pathOriginal . $file;
					if(!file_exists($path)) {
						throw new CriticalException("$path does not exist, but it should!");
					}
					
					$backupPath = $pathServerBackup . $file;
					if(is_dir($path)) {
						// Windows sometimes throws some weird permission denied exceptions if we try to move the api-folder (probably because it is "used" by the server).
						// So we move the files one by one instead.
						FileSystemBasics::moveOneByOne($path, $backupPath);
					}
					else {
						if(!rename($path, $backupPath)) {
							throw new CriticalException("Renaming $path to $backupPath failed");
						}
					}

					$this->flushProgress(1, ++$step, $needBackupTotal);
				}
			}
			catch(Throwable $error) {
				if(file_exists($pathServerBackup)) {
					try {
						$this->revert();
					}
					catch(Throwable $error2) {
						throw new CriticalException("Something went horribly wrong when moving files to backup! While trying to recover, the following error happened: " . $error2->getMessage() . ". The original error: " . $error->getMessage() . ". You might be able to recover manually, by moving the remaining files from $this->pathBackup to $this->pathOriginal");
					}
				}
				throw new CriticalException("Could not move files to backup location. The original files have been restored. Error: " . $error->getMessage());
			}
			
			
			// Move update into main structure:
			try {
				$this->flushProgress(2, 0, 1);
				FileSystemBasics::moveOneByOne($pathServerUpdate, $this->pathOriginal, false, function(int $step, int $total) {
					$this->flushProgress(2, $step, $total);
				});
				
				if(function_exists('opcache_reset')) {//for servers using Zend bytecode cache
					opcache_reset();
				}
			}
			catch(Throwable $error) {
				try {
					if(file_exists($this->pathStructureFile)) {
						$structure = $this->getStructureFileList(); // if it exists, we can delete all update files, if not the array is empty and we have to leave them or we would risk deleting non-ESMira files
						
						$needBackupTotal = count($structure);
						$step = $needBackupTotal;
						
						foreach($structure as $file) {
							$path = $this->pathOriginal . $file;
							if(!file_exists($path)) {
								continue;
							}
							
							if(is_dir($path)) {
								FileSystemBasics::emptyFolder($path);
							} else {
								unlink($path);
							}
							$this->flushProgress(2, --$step, $needBackupTotal);
						}
					}
					$this->revert();
				}
				catch(Throwable $error2) {
					throw new CriticalException("Something went horribly wrong when updating files! While trying to recover, the following error happened: " . $error2->getMessage() . ". The original error: " . $error->getMessage() . ". You might be able to recover manually, by moving the remaining files from $this->pathBackup to $this->pathOriginal");
				}
				throw new CriticalException("Could not move update. The original files have been restored. Error: " . $error->getMessage());
			}
			
			// Remove empty folders:
			
			if(!rmdir($pathServerUpdate)) {
				throw new CriticalException("Could not remove update folder $pathServerUpdate. It is supposed to be empty");
			}
			if(file_exists($pathServerBackup) && (!FileSystemBasics::emptyFolder($pathServerBackup) || !rmdir($pathServerBackup))) {
				throw new CriticalException('Failed to clean up backup');
			}
			
			$this->sse->flushFinished();
		}
		catch(Throwable $e) {
			$dataStore->setMaintenanceMode(false);
			$this->sse->flushFailed($e->getMessage());
		}
		finally {
			if(file_exists($this->pathUpdate) && FileSystemBasics::isDirEmpty($this->pathUpdate)) {
				rmdir($this->pathUpdate);
			}
			if(file_exists($this->pathBackup) && FileSystemBasics::isDirEmpty($this->pathBackup)) {
				rmdir($this->pathBackup);
			}
		}
	}
	
	function exec(): array {
		throw new CriticalException('Internal error. UpdateStepReplace can only be used with execAndOutput()');
	}
	
	protected function isReady(): bool {
		return true;
	}
}