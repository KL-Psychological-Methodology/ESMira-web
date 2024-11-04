<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\fileSystem\DataStoreFS;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\Paths;
use backend\subStores\SnapshotStore;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;

class restoreSnapshot extends HasAdminPermission
{
	//we dont want to blindly copy everything over in case there are non ESMira-files in our main folder:
	public const NEEDS_BACKUP = ['api', 'backend',  'frontend', 'locales', '.htaccess', 'CHANGELOG.md', 'index.php', 'index_nojs.php', 'LICENSE', 'README.md'];
	public const TEMP_DIR = DIR_BASE . ".tmp";
	public const TEMP_SERVER_FILES = self::TEMP_DIR . "/esmira";
	public const TEMP_DATA_FILES = self::TEMP_DIR . "/esmira_data";
	public const TEMP_INFO = self::TEMP_DIR . "/info.json";

	/**
	 * @var string
	 */
	protected $folderPathSource;
	/**
	 * @var string
	 */
	protected $folderPathBackup;

	private $filesToRetain = [];

	public function __construct(
		string $folderPathSource = DIR_BASE,
		string $folderPathBackup = Paths::FOLDER_SERVER_BACKUP
	) {
		parent::__construct();
		//we define them here so we can change the location for testing:
		$this->folderPathSource = $folderPathSource;
		$this->folderPathBackup = $folderPathBackup;
	}

	/**
	 * @throws CriticalException
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
	protected function revertBackup($msg): PageFlowException
	{
		$revertFailedList = []; {
		}
		//now, copy everything back from the backup folder:
		if (file_exists($this->folderPathBackup)) {
			$handle = opendir($this->folderPathBackup);
			while ($file = readdir($handle)) {
				if ($file = '.' || $file == '..') {
					continue;
				}

				$oldLocation = $this->folderPathBackup . $file;
				$newLocation = $this->folderPathSource . $file;

				//source contains the files from the update. So remove them first:
				if (file_exists($newLocation)) {
					if (is_file($newLocation)) {
						unlink($newLocation);
					} else {
						FileSystemBasics::emptyFolder($newLocation);
						rmdir($newLocation);
					}
				}

				//Now we move the stuff back:
				if (!@rename($oldLocation, $newLocation)) {
					$revertFailedList[] = $newLocation;
				}
			}
			closedir($handle);
		}

		if (count($revertFailedList)) {
			throw new PageFlowException("Reverting backup failed! The following files are still in the backup folder: $revertFailedList\nReverting was caused by this error: \n$msg");
		} else {
			if (file_exists($this->folderPathBackup) && FileSystemBasics::emptyFolder($this->folderPathBackup)) {
				rmdir($this->folderPathBackup);
			}
		}

		return new PageFlowException($msg);
	}

	/**
	 * Windows throws some weird permission denied exceptions if we try to move the api-folder (probably because it is "used" by the server. So we move the files one by one.
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
	private function move(string $oldLocation, string $newLocation)
	{
		if (is_file($oldLocation)) {
			if (!rename($oldLocation, $newLocation)) {
				throw $this->revertBackup("Renaming $oldLocation to $newLocation failed. Reverting...");
			}
		} else {
			$handle = opendir($oldLocation);
			while ($file = readdir($handle)) {
				if ($file == '.' || $file == '..') {
					continue;
				}

				if (!file_exists($newLocation)) {
					mkdir($newLocation, 0744);
				}
				$this->move("$oldLocation/$file", "$newLocation/$file");
			}
			closedir($handle);
		}
	}

	/**
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
	private function moveEverythingToBackupLocation()
	{
		if (!file_exists($this->folderPathBackup)) {
			FileSystemBasics::createFolder($this->folderPathBackup);
		}

		foreach (self::NEEDS_BACKUP as $file) {
			$oldLocation = $this->folderPathSource . $file;
			$newLocation = $this->folderPathBackup . $file;

			if (!file_exists($oldLocation)) {
				continue;
			}
			if (file_exists($newLocation)) {
				throw $this->revertBackup("Critical error! $newLocation already exists. This should never happen. Please check file structure manually.");
			}

			$this->move($oldLocation, $newLocation);
		}

		//remember non-ESMira files we want to keep in case we need to revert
		$handle = opendir($this->folderPathSource);
		while ($file = readdir($handle)) {
			$this->filesToRetain[] = $file;
		}
		closedir($handle);
	}

	/**
	 * @throws PageFlowException
	 */
	private function checkSnapshot()
	{
		if (!file_exists(self::TEMP_INFO)) {
			throw new PageFlowException("No info.json found in snapshot.");
		}
		$snapshotConfigPath = self::TEMP_SERVER_FILES . "/" . Paths::SUB_PATH_CONFIG;
		if (!file_exists($snapshotConfigPath)) {
			throw new PageFlowException("No configuration file found in snaphot.");
		}

		$snapshotConfigFile = fopen($snapshotConfigPath, "r");
		$snapshotConfig = fread($snapshotConfigFile, filesize($snapshotConfigPath));
		fclose($snapshotConfigFile);

		$dataStore = Configs::getDataStore();
		if ($dataStore instanceof DataStoreFS) {
			if (preg_match("/'dataStore' => '.*DataStoreFS'/", $snapshotConfig) != 1) {
				throw new PageFlowException("DataStore of snapshot not compatible with ESMira's current configuration.");
			}
		} else {
			throw new PageFlowException("Found DataStore not implemented in RestoreSnapshot.php");
		}
	}

	/**
	 * @throws PageFlowException
	 */
	private function unpackToTemp(SnapshotStore $snapshotStore)
	{
		if (!($snapshotStore->getSnapshotInfo()["hasSnapshot"])) {
			throw new PageFlowException("No snapshot present.");
		}
		$zip = new ZipArchive();

		$zipPath = $snapshotStore->getSnapshotZipPath();
		if (!($zip->open($zipPath) === true)) {
			throw new PageFlowException("Could not open snaphot zip file.");
		}

		if (!($zip->extractTo(self::TEMP_DIR))) {
			$zip->close();
			throw new PageFlowException("Could not extract snapshot zip file.");
		}
		$zip->close();
	}

	public function exec(): array
	{
		set_time_limit(10 * 60);

		if (file_exists(self::TEMP_DIR)) {
			throw new PageFlowException("A temporary directory for the snapshot already exists at " . self::TEMP_DIR);
		}
		if (file_exists($this->folderPathBackup)) {
			throw new PageFlowException("A backup seems to already exist at: $this->folderPathBackup");
		}

		FileSystemBasics::createFolder(self::TEMP_DIR);

		try {
			$snapshotStore = Configs::getDataStore()->getSnapshotStore();
			$snapshotStore->storeOldConfigs();
			$this->unpackToTemp($snapshotStore);
			$this->checkSnapshot();

			$dataBackup = Configs::getDataStore()->getDataBackupStore();
			$dataBackup->backupData();

			try {
				$this->moveEverythingToBackupLocation();

				$paths = [
					[
						"source" => self::TEMP_SERVER_FILES,
						"target" => DIR_BASE
					],
					[
						"source" => self::TEMP_DATA_FILES,
						"target" => substr(PathsFS::folderData(), 0, -1)
					]
				];

				foreach ($paths as $pathsConfig) {

					$directory = new RecursiveDirectoryIterator($pathsConfig["source"]);
					$files = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);

					foreach ($files as $file) {
						$filePath = $file->getRealPath();
						$relativePath = substr($filePath, strlen($pathsConfig["source"]));
						$targetPath = $pathsConfig["target"] . "/" . $relativePath;
						if ($file->isDir()) {
							FileSystemBasics::createFolder($targetPath);
						} else {
							if (!copy($filePath, $targetPath)) {
								throw new PageFlowException("Could not copy $filePath to $targetPath. Reverting...");
							}
						}
					}
				}

				$snapshotStore->restoreOldConfigs();

				if (file_exists($this->folderPathBackup) && (!FileSystemBasics::emptyFolder($this->folderPathBackup) || !@rmdir($this->folderPathBackup)))
					throw new PageFlowException("Failed to clean up backup. Snapshot restore was successful, but backup folder needs to manually be cleaned: $this->folderPathBackup");
				$dataBackup->deleteBackup();
			} catch (Throwable $e) {
				$dataBackup->restoreData();
				throw $this->revertBackup($e->getMessage());
			}
		} catch (Throwable $e) {
			throw $e;
		} finally {
			FileSystemBasics::emptyFolder(DIR_BASE . ".tmp");
			rmdir(self::TEMP_DIR);
		}

		return [];
	}
}