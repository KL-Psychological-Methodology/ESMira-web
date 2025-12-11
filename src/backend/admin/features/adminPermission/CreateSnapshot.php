<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\FileSystemBasics;
use backend\Paths;
use backend\SSE;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;

class CreateSnapshot extends HasAdminPermission {
	private const MAX_STAGES = 3;
	
	protected string $pathStructureFile;
	protected string $pathHome;
	private SSE $sse;
	
	/**
	 * Constructor is only needed for testing.
	 * @param string $pathStructureFile Path to the config file.
	 * @param string $pathHome Path to DIR_BASE.
	 * @param SSE|null $sse SSE object to use for sending progress updates.
	 * @throws PageFlowException | CriticalException
	 */
	public function __construct(
		string $pathStructureFile = Paths::FILE_STRUCTURE,
		string $pathHome = DIR_BASE,
		?SSE $sse = null
	) {
		parent::__construct();
		$this->pathStructureFile = $pathStructureFile;
		$this->pathHome = $pathHome;
		$this->sse = $sse ?? new SSE();
	}
	
	private function flushProgress(int $stage, int $step, int $total): void {
		$this->sse->flushProgress($stage, self::MAX_STAGES, $step, $total);
	}
	
	function execAndOutput() {
		$this->sse->sendHeader();
		
		$datastore = Configs::getDataStore();
		$snapshotName = $_GET['name'] ?? "snapshot";
		$snapshotStore = $datastore->getSnapshotStore();
		$pathZip = $snapshotStore->getSnapshotZipPath($snapshotName);
		
		
		$zip = new ZipArchive;
		
		try{
			if(!file_exists(dirname($pathZip))) {
				FileSystemBasics::createFolder(dirname($pathZip), true);
			}
			if(file_exists($pathZip)) {
				throw new CriticalException("Snapshot $snapshotName already exists!");
			}
			if(!$zip->open($pathZip, ZIPARCHIVE::CREATE)) {
				throw new CriticalException('Could not open new zip file for snapshot.');
			}
			
			
			// Zip ESMira structure
			
			$this->flushProgress(1, 0, 1);
			$structure = json_decode(file_get_contents($this->pathStructureFile));
			$step = 0;
			$needBackupTotal = count($structure);
			
			foreach($structure as $structureFile) {
				$path = $this->pathHome . $structureFile;
				if(!file_exists($path)) {
					throw new CriticalException("$path does not exist, but it should!");
				}
				
				if(is_dir($path)) {
					$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::LEAVES_ONLY);
					
					foreach($files as $file) {
						if($file->isDir()) {
							continue;
						}
						
						$filePath = $file->getRealPath();
						$relativePath = substr($filePath, strlen($path));
						
						if(!$zip->addFile($filePath, Paths::SUB_PATH_SERVER_UPDATE_FILES . $structureFile . $relativePath)) {
							throw new CriticalException("Could not add $filePath to snapshot zip.");
						}
					}
				}
				else {
					if(!$zip->addFile($path, Paths::SUB_PATH_SERVER_UPDATE_FILES . $structureFile)) {
						throw new CriticalException("Could not add $path to snapshot zip.");
					}
				}
				
				$this->flushProgress(1, ++$step, $needBackupTotal);
			}
			
			
			// Save ESMira data
			
			$datastore->setMaintenanceMode(true);
			$snapshotStore->addDataToZip($zip, function(int $step, int $total) {
				$this->flushProgress(2, $step, $total);
			});
			$datastore->setMaintenanceMode(false);
			$this->flushProgress(3, 0, 1);
		}
		catch(Throwable $e) {
			if(file_exists($pathZip)) {
				unlink($pathZip);
			}
			$this->sse->flushFailed($e->getMessage());
			return;
		}
		finally {
			if(!$zip->close()) {
				$this->sse->flushFailed('Unable to close zip file');
				return;
			}
		}
		
		$this->sse->flushFinished();
	}
	
	function exec(): array {
		throw new CriticalException('Internal error. CreateSnapshot can only be used with execAndOutput()');
	}
}