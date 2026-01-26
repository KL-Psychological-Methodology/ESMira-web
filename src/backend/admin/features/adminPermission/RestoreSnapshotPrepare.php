<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\FileSystemBasics;
use backend\Paths;
use Throwable;
use ZipArchive;

/**
 * Call order:
 *  RestoreSnapshotPrepare -> UpdateStepReplace -> RestoreSnapshotData
 */
class RestoreSnapshotPrepare extends HasAdminPermission {
	
	/**
	 * Only needed for testing.
	 * @var string
	 */
	protected $pathUpdate;
	
	/**
	 * Constructor is only needed for testing.
	 * @param string $pathUpdate Path to the folder where the update should be extracted to.
	 * @throws CriticalException | PageFlowException
	 */
	public function __construct(string $pathUpdate = Paths::FOLDER_SERVER_UPDATE) {
		parent::__construct();
		$this->pathUpdate = $pathUpdate;
	}
	
    function exec(): array {
		try {
			$snapshotName = $_POST['name'];
			$snapshotStore = Configs::getDataStore()->getSnapshotStore();
			$pathZip = $snapshotStore->getSnapshotZipPath($snapshotName);
			
			if(file_exists($this->pathUpdate)) {
				throw new CriticalException("$this->pathUpdate already exists. Please manually remove it and try again.");
			}
			FileSystemBasics::createFolder($this->pathUpdate);
			$zip = new ZipArchive;
			if(!$zip->open($pathZip)) {
				throw new CriticalException("Could not open $pathZip.");
			}
			if(!$zip->extractTo($this->pathUpdate)) {
				throw new CriticalException("Could not unzip update to $this->pathUpdate.");
			}
			$zip->close();
		}
		catch(Throwable $error) {
			FileSystemBasics::emptyFolder($this->pathUpdate);
			throw new CriticalException("Could not restore snapshot. Error: " . $error->getMessage());
		}

        return [];
    }
}