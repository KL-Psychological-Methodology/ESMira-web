<?php

namespace backend\fileSystem\subStores;

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\Paths;
use backend\subStores\SnapshotStore;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveFilterIterator;
use RecursiveIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;

class SnapshotStoreFS implements SnapshotStore
{

	const SNAPSHOTS_VERSION = 1;

	const NEEDS_BACKUP_DIRECTORIES = ['api', 'backend',  'frontend', 'locales'];
	const NEEDS_BACKUP_FILES = ['.htaccess', 'CHANGELOG.md', 'index.php', 'index_nojs.php', 'LICENSE', 'README.md'];
	const ZIP_PATH_SYSTEM = "esmira";
	const ZIP_PATH_DATA = "esmira_data";
	const ZIP_PATH_METADATA = "info.json";

	const KEY_VERSION = "snapshotsVersion";
	const KEY_DATA_DIR = "dataFolder_path";
	const KEY_STORAGE_TYPE = "storage";

	const STORAGE_TYPE = "filesystem";

	const FOLDER_PATH_SOURCE = DIR_BASE;
	const SNAPSHOT_ARCHIVE_NAME = "snapshot.zip";

	const PERSISTENT_KEYS = [self::KEY_DATA_DIR];

	private $oldConfigVars = [];
	
	public function createSnapshot()
	{
		FileSystemBasics::createFolder(PathsFS::folderSnapshot());
		$pathZip = $this->filePathZip();

		$this->deleteSnapshot();
		
		$zip = new ZipArchive();
		
		try {
			FileSystemBasics::createFolder(PathsFS::folderSnapshot());


			if (!($zip->open($pathZip, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === true))
				throw new PageFlowException('Could not open new zip file for snapshot.');

			$dataPath = rtrim(PathsFS::folderData(), '\\/');
			$dataPath = realpath($dataPath);

			// Create Metadata JSON
			$info = [
				self::KEY_VERSION => self::SNAPSHOTS_VERSION,
				self::KEY_STORAGE_TYPE => self::STORAGE_TYPE,
				self::KEY_DATA_DIR => $dataPath,
			];

			if (!@$zip->addFromString(self::ZIP_PATH_METADATA, json_encode($info)))
				throw new PageFlowException('Could not add info file to snapshot zip.');

			echo "event: progress\n";
			echo "data: {\"stage\": 1, \"progress\": 0}\n\n";
			if (ob_get_contents())
				ob_end_flush();
			flush();

			// Save ESMira system

			$step = 1;
			$needBackupTotal = count(self::NEEDS_BACKUP_DIRECTORIES) + count(self::NEEDS_BACKUP_FILES);

			foreach (self::NEEDS_BACKUP_DIRECTORIES as $folderPath) {
				$percent = round(($step / $needBackupTotal * 100));
				$step++;

				$rootPath = rtrim(self::FOLDER_PATH_SOURCE . $folderPath);
				$rootPath = realpath($rootPath);


				$files = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($rootPath),
					RecursiveIteratorIterator::LEAVES_ONLY
				);

				foreach ($files as $file) {
					if (!$file->isDir()) {
						$filePath = $file->getRealPath();
						$relativePath = substr($filePath, strlen($rootPath) + 1);

						if (!@$zip->addFile($filePath, self::ZIP_PATH_SYSTEM . "/" . $folderPath . "/" . $relativePath))
							throw new PageFlowException('Could not add file to snapshot zip.');
					}
				}
				echo "event: progress\n";
				echo "data: {\"stage\": 1, \"progress\": $percent}\n\n";
				if (ob_get_contents())
					ob_end_flush();
				flush();
			}

			foreach (self::NEEDS_BACKUP_FILES as $folderPath) {
				$percent = round(($step / $needBackupTotal * 100));
				$step++;

				$rootPath = rtrim(self::FOLDER_PATH_SOURCE . $folderPath);
				$rootPath = realpath($rootPath);

				if (!@$zip->addFile($rootPath, self::ZIP_PATH_SYSTEM . "/" . $folderPath))
					throw new PageFlowException('Could not add file to snapshot zip.');

				echo "event: progress\n";
				echo "data: {\"stage\": 1, \"progress\": $percent}\n\n";
				if (ob_get_contents())
					ob_end_flush();
				flush();
			}

			echo "event: progress\n";
			echo "data: {\"stage\": 2, \"progress\": 0}\n\n";
			if (ob_get_contents())
				ob_end_flush();
			flush();

			// Save ESMira data


			$directory = new RecursiveDirectoryIterator($dataPath);
			$filter = new RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
				$exclude = [basename(PathsFS::folderSnapshot()), basename(PathsFS::folderDataBackup())];
				if ($current->isDir()) {
					return !in_array($current->getBasename(), $exclude);
				} else {
					return TRUE;
				}
			});
			$files = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::LEAVES_ONLY);

			$numFiles = iterator_count($files);
			$fileNum = 0;

			foreach ($files as $file) {
				$filePath = $file->getRealPath();
				$relativePath = substr($filePath, strlen($dataPath) + 1);
				$targetPath = self::ZIP_PATH_DATA . "/" . $relativePath;
				if ($file->isDir()) {
					if ($this->isDirEmpty($filePath) && !$zip->addEmptyDir($targetPath))
						throw new PageFlowException("Could not add directory $targetPath to snapshot zip.");
				} else if (!$zip->addFile($filePath, $targetPath)) {
					throw new PageFlowException("Could not add file $targetPath to snapshot zip.");
				}

				$fileNum++;
				$percent = round(($fileNum / $numFiles) * 100);
				echo "event: progress\n";
				echo "data: {\"stage\": 2, \"progress\": $percent}\n\n";
				if (ob_get_contents())
					ob_end_flush();
				flush();
			}

			if (!$zip->close()) {
				echo "event: progress\n";
				echo "data: {\"stage\": 3, \"progress\": $percent}\n\n";
				if (ob_get_contents())
					ob_end_flush();
				flush();
				throw new PageFlowException("Unable to close zip file.");
			}
			echo "event: progress\n";
			echo "data: {\"stage\": 4, \"progress\": $percent}\n\n";
			if (ob_get_contents())
				ob_end_flush();
			flush();
		} catch (Throwable $e) {
			$zip->close();
			unlink($pathZip);
			throw $e;
		}
	}

	public function getSnapshotInfo(): array
	{
		$filePath = $this->filePathZip();
		$fileExists = file_exists($filePath);
		$info = [
			"hasSnapshot" => $fileExists,
			"fileChanged" => 0,
			"fileSize" => 0
		];

		if ($fileExists) {
			$info["fileChanged"] = filectime($filePath);
			$info["fileSize"] = filesize($filePath);
		}

		return $info;
	}

	public function deleteSnapshot()
	{
		unlink($this->filePathZip());
		$this->clearUploads("");
	}

	public function restoreSnapshot()
	{
		$extractDir = PathsFS::folderSnapshot() . 'temp';

		$sourcePath = DIR_BASE;
		$dataPath = PathsFS::folderData();

		$snapshotSourcePath = $extractDir . self::ZIP_PATH_SYSTEM;
		$snapshotDataPath = $extractDir . self::ZIP_PATH_DATA;

		try {
			// Extract Snapshot
			$zip = new ZipArchive();
			if (!@$zip->open($this->filePathZip()))
				throw new PageFlowException('Could not open snapshot zip.');
			if (!@$zip->extractTo($extractDir))
				throw new PageFlowException('Could not extract snapshot zip.');
			$zip->close();

			// Restore System

			$handle = opendir($snapshotSourcePath);
			while ($file = readdir($handle)) {
				if ($file == '.' || $file == '..')
					continue;

				$oldLocation = $snapshotSourcePath . $file;
				$newLocation = $sourcePath . $file;
				// Remove all existing files/directories that the snapshot will want to overwrite to prevent leftover data
				unlink($newLocation);

				if (!@$this->move($oldLocation, $newLocation))
					throw new PageFlowException("Could not move $oldLocation to $newLocation during snapshot extraction");
			}

			// Restore Data

			$handle = opendir($snapshotDataPath);
			while ($file = readdir($handle)) {
				if ($file == '.' || $file == '..')
					continue;

				$oldLocation = $snapshotDataPath . $file;
				$newLocation = $dataPath . $file;

				unlink($newLocation);

				if (!@$this->move($oldLocation, $newLocation))
					throw new PageFlowException("Could not move $oldLocation to $newLocation during snapshot extraction");
			}

			unlink($extractDir);
		} catch (Throwable $e) {
			unlink($extractDir);
			throw $e;
		}
	}

	public function getSnapshotZipPath(): string
	{
		return $this->filePathZip();
	}

	private function getUploadPartPath(string $name)
	{
		return PathsFS::folderSnapshot() . $name . ".part";
	}

	/**
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
	public function storeUploadPart(string $partPath, string $name)
	{
		FileSystemBasics::createFolder(PathsFS::folderSnapshot());
		if (!$out = @fopen($this->getUploadPartPath($name), "ab"))
			throw new PageFlowException("Could not open temporary upload output file");
		if (!$in = fopen($partPath, "rb"))
			throw new PageFlowException("Could not open temporary upload input file");
		while ($buff = fread($in, 4096))
			fwrite($out, $buff);
		@fclose($out);
		@fclose($in);
	}

	public function completeUpload(string $name)
	{
		rename($this->getUploadPartPath($name), $this->getSnapshotZipPath());
	}

	public function clearUploads(string $currentName)
	{
		$handle = opendir(PathsFS::folderSnapshot());
		while ($file = readdir($handle)) {
			if ($file == $currentName . ".part")
				continue;
			if (preg_match('/\.part$/', $file))
				@unlink(PathsFS::folderSnapshot() . DIRECTORY_SEPARATOR . $file);
		}
		closedir($handle);
	}

	private function filePathZip(): string
	{
		return PathsFS::folderSnapshot() . self::SNAPSHOT_ARCHIVE_NAME;
	}

	/**
	 * Windows throws some weird permission denied exceptions if we try to move the api-folder (probably because it is "used" by the server. So we move the files one by one.
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
	private function move(string $oldLocation, string $newLocation)
	{
		if (is_file($oldLocation)) {
			if (!rename($oldLocation, $newLocation))
				throw new PageFlowException("Renaming $oldLocation to $newLocation failed.");
		} else {
			$handle = opendir($oldLocation);
			while ($file = readdir($handle)) {
				if ($file == '.' || $file == '..')
					continue;

				if (!file_exists($newLocation))
					mkdir($newLocation, 0744);
				if (!@$this->move("$oldLocation/$file", "$newLocation/$file"))
					throw new PageFlowException("Moving $oldLocation to $newLocation failed.");
			}
			closedir($handle);
		}
	}

	private function isDirEmpty($directory): bool
	{
		$handle = opendir($directory);
		if ($handle === false) {
			return true;
		}
		while (($file = readdir($handle)) !== false) {
			if ($file != "." && $file != "..") {
				return false;
			}
		}
		return true;
	}

	public function storeOldConfigs()
	{
		foreach (self::PERSISTENT_KEYS as $key) {
			$this->oldConfigVars[$key] = Configs::get($key);
		}
	}

	public function restoreOldConfigs()
	{
		FileSystemBasics::writeServerConfigs($this->oldConfigVars);
	}
}
