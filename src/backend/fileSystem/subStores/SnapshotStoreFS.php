<?php

namespace backend\fileSystem\subStores;

use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\Paths;
use backend\subStores\SnapshotStore;
use Iterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;
use ZipArchive;

class SnapshotStoreFS implements SnapshotStore {
	private function createDataIterator(): Iterator {
		$pathSnapshotFolder = PathsFS::folderSnapshots();
		$pathData = PathsFS::folderData();
		$directory = new RecursiveDirectoryIterator($pathData);
		$snapshotsFolderName = basename($pathSnapshotFolder);
		
		$filter = new RecursiveCallbackFilterIterator($directory, function(SplFileInfo $current, $key, $iterator) use($snapshotsFolderName) {
			return $current->getFilename() != '.' && $current->getFilename() != '..' && $current->getBasename() != $snapshotsFolderName;
		});
		return new RecursiveIteratorIterator($filter);
	}
	
	/**
	 * @throws CriticalException
	 */
	public function addDataToZip(ZipArchive $zip, callable $reportProgress): void {
		$pathData = PathsFS::folderData();
		
		$iterator = $this->createDataIterator();
		$pathDataStringLength = strlen($pathData);
		
		$totalFiles = iterator_count($iterator);
		$fileNum = 0;
		
		foreach($iterator as $file) {
			$filePath = $file->getRealPath();
			$relativePath = substr($filePath, $pathDataStringLength);
			$targetPath = PathsFS::FILENAME_DATA .'/' . $relativePath;
			
			if(!file_exists($filePath)) {
				throw new CriticalException("$targetPath does not exist, but it should!");
			}
			
			if($file->isDir()) {
				if(FileSystemBasics::isDirEmpty($filePath) && !$zip->addEmptyDir($targetPath)) {
					throw new CriticalException("Could not add directory $targetPath to snapshot zip.");
				}
			}
			else if(!$zip->addFile($filePath, $targetPath)) {
				throw new CriticalException("Could not add file $targetPath to snapshot zip.");
			}
			
			$reportProgress(++$fileNum, $totalFiles);
		}
	}
	
	/**
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
	public function restoreDataFromSnapshot(string $pathUpdate, string $pathBackup, callable $reportProgress): void {
		$maxStages = 2;
		$pathUpdate .= PathsFS::FILENAME_DATA . '/';
		$pathBackup .= PathsFS::FILENAME_DATA . '/';
		$pathData = PathsFS::folderData();
		$iterator = $this->createDataIterator();
		
		if(!file_exists($pathUpdate)) {
			throw new CriticalException("Could not find update at $pathUpdate");
		}
		if(file_exists($pathBackup)) {
			throw new CriticalException("$pathBackup already exists!");
		}
		
		// Move existing files to backup:
		try {
			$reportProgress(1, $maxStages, 0, 1);
			FileSystemBasics::createFolder($pathBackup, true);
			FileSystemBasics::moveOneByOne($pathData, $pathBackup, false, function(int $step, int $total) use($reportProgress, $maxStages) {
				$reportProgress(1, $maxStages, $step, $total);
			}, $iterator);
		}
		catch(Throwable $error) {
			if(file_exists($pathBackup)) {
				try {
					FileSystemBasics::moveOneByOne($pathBackup, $pathData, true, function(int $step, int $total) use($reportProgress, $maxStages) {
						$reportProgress(1, $maxStages, $total - $step, $total);
					});
				}
				catch(Throwable $error2) {
					throw new CriticalException("Something went horribly wrong when moving data files to backup! While trying to recover, the following error happened: " . $error2->getMessage() . ". The original error: " . $error->getMessage() . ". You might be able to recover manually, by moving the remaining files from $pathBackup to $pathData");
				}
			}
			throw new CriticalException("Could not move files to backup location. The original files have been restored. Error: " . $error->getMessage());
		}
		
		
		// Move update into main structure:
		try {
			$reportProgress(2, $maxStages, 0, 1);
			FileSystemBasics::moveOneByOne($pathUpdate, $pathData, false, function(int $step, int $total) use($reportProgress, $maxStages) {
				$reportProgress(2, $maxStages, $step, $total);
			});
		}
		catch(Throwable $error) {
			try {
				foreach($iterator as $file) {
					if($file->isDir()) {
						rmdir($file->getRealPath());
					}
					else {
						unlink($file->getRealPath());
					}
				}
				FileSystemBasics::moveOneByOne($pathBackup, $pathData, true, function(int $step, int $total) use($reportProgress, $maxStages) {
					$reportProgress(1, $maxStages, $total - $step, $total);
				});
			}
			catch(Throwable $error2) {
				throw new CriticalException("Something went horribly wrong when updating files! While trying to recover, the following error happened: " . $error2->getMessage() . ". The original error: " . $error->getMessage() . ". You might be able to recover manually, by moving the remaining files from $pathBackup to $pathData");
			}
			throw new CriticalException("Could not move update. The original files have been restored. Error: " . $error->getMessage());
		}
		
		// Remove empty folders:
		
		if(!rmdir($pathUpdate)) {
			throw new CriticalException("Could not remove update folder $pathUpdate. It is supposed to be empty");
		}
		if(file_exists($pathBackup) && (!FileSystemBasics::emptyFolder($pathBackup) || !rmdir($pathBackup))) {
			throw new CriticalException('Failed to clean up backup');
		}
	}
	
	public function getSnapshotZipPath(string $snapshotName): string {
		return PathsFS::fileSnapshotZip($snapshotName);
	}
	
	public function listSnapshots(): array {
		$filePath = PathsFS::folderSnapshots();
		$files = scandir($filePath);
		$snapshots = [];
		foreach($files as $file) {
			if(preg_match('/^(.+)\.zip$/', $file, $match)) {
				$snapshots[] = [
					'name' => Paths::getFromUrlFriendly($match[1]),
					'created' => filemtime($filePath . $file),
					'size' => filesize($filePath . $file)
				];
			}
		}
		return $snapshots;
	}
}
