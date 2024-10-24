<?php

namespace backend\fileSystem\subStores;

use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\subStores\DataBackupStore;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DataBackupStoreFS implements DataBackupStore
{
	/**
	 * @throws CriticalException
	 */
	public function backupData()
	{
		$backupPath = PathsFS::folderDataBackup();

		FileSystemBasics::createFolder($backupPath);

		if (self::backupExists())
			self::deleteBackup();
		FileSystemBasics::createFolder($backupPath);
		$folderData = PathsFS::folderData();

		$exclude = [
			realpath(PathsFS::folderSnapshot()),
			realpath(PathsFS::folderDataBackup())
		];

		$this->copyRecursively($folderData, $backupPath, $exclude);
	}

	/**
	 * @throws CriticalException
	 */
	public function restoreData()
	{
		$this->cleanDataDirectory();
		$dataPath = realpath(PathsFS::folderData());
		$backupPath = PathsFS::folderDataBackup();
		$this->copyRecursively($backupPath, $dataPath);
		$this->deleteBackup();
	}

	public function deleteBackup()
	{
		@rmdir(PathsFS::folderDataBackup());
	}

	private function backupExists(): bool
	{
		return file_exists(PathsFS::folderDataBackup());
	}

	private function copyRecursively(string $sourceDirectory, string $destinationDirectory, array $exclude = [])
	{
		$directory = new RecursiveDirectoryIterator($sourceDirectory);
		$filter = new RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
			$exclude = [basename(PathsFS::folderSnapshot()), basename(PathsFS::folderDataBackup())];
			if ($current->isDir()) {
				return !in_array($current->getBasename(), $exclude);
			} else {
				return TRUE;
			}
		});
		$files = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::LEAVES_ONLY);

		foreach ($files as $file) {
			$filePath = $file->getRealPath();
			$relativePath = substr($filePath, strlen($sourceDirectory));
			$targetPath = $destinationDirectory . $relativePath;
			if ($file->isDir()) {
				FileSystemBasics::createFolder($targetPath);
			} else {
				copy($filePath, $targetPath);
			}
		}
	}

	private function cleanDataDirectory()
	{
		$backupPath = PathsFS::folderDataBackup();
		$dataPath = PathsFS::folderData();
		$handle = opendir($dataPath);
		while ($file = readdir($handle)) {
			if ($file == '.' || $file == '..' || $file == basename($backupPath))
				continue;
			unlink($dataPath . '/' . $file);
		}
		closedir($handle);
	}
}
