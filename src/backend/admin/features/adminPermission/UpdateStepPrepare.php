<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\exceptions\CriticalException;
use backend\FileSystemBasics;
use backend\Paths;
use backend\exceptions\PageFlowException;
use Throwable;
use ZipArchive;

/**
 * Call order:
 * UpdateStepPrepare -> UpdateStepReplace -> UpdateVersion
 */
class UpdateStepPrepare extends HasAdminPermission {
	/**
	 * Only needed for testing.
	 * @var string
	 */
	protected $pathConfigFile;
	
	/**
	 * Only needed for testing.
	 * @var string
	 */
	protected $pathUpdateZip;
	
	/**
	 * Only needed for testing.
	 * @var string
	 */
	protected $pathUpdate;
	
	/**
	 * Constructor is only needed for testing.
	 * @param string $pathConfigFile Path to the config file.
	 * @param string $pathUpdateZip Path to the zipped update file.
	 * @param string $pathUpdate Path to the folder where the update should be extracted to.
	 * @throws CriticalException
	 * @throws PageFlowException
	 */
	public function __construct(string $pathConfigFile = Paths::FILE_CONFIG, string $pathUpdateZip = Paths::FILE_SERVER_UPDATE, string $pathUpdate = Paths::FOLDER_SERVER_UPDATE) {
		parent::__construct();
		$this->pathConfigFile = $pathConfigFile;
		$this->pathUpdateZip = $pathUpdateZip;
		$this->pathUpdate = $pathUpdate;
	}
	
	/**
	 * @throws CriticalException
	 */
	function revert(Throwable $error) {
		try {
			if(file_exists($this->pathUpdate)) {
				FileSystemBasics::emptyFolder($this->pathUpdate);
				rmdir($this->pathUpdate);
			}
		}
		catch(Throwable $error) {
			throw new CriticalException('Could not clean up update folder after update was canceled because of error: ' . $error->getMessage());
		}
		throw new CriticalException('Could not prepare the update because: ' . $error->getMessage());
	}
	
	/**
	 * @throws Throwable
	 * @throws CriticalException
	 */
	function exec(): array {
		if(!file_exists($this->pathUpdateZip)) {
			throw new CriticalException("Missing $this->pathUpdateZip");
		}
		else if(file_exists($this->pathUpdate)) {
			throw new CriticalException("$this->pathUpdate already exists. Please manually remove it and try again.");
		}
		
		try {
			//unpacking update:
			FileSystemBasics::createFolder($this->pathUpdate . Paths::SUB_PATH_SERVER_UPDATE_FILES, true);
			$zip = new ZipArchive;
			if(!$zip->open($this->pathUpdateZip)) {
				throw new CriticalException("Could not open the the zipped update at $this->pathUpdateZip.");
			}
			if(!$zip->extractTo($this->pathUpdate . Paths::SUB_PATH_SERVER_UPDATE_FILES)) {
				throw new CriticalException("Could not unzip update to $this->pathUpdate.");
			}
			$zip->close();
			unlink($this->pathUpdateZip);
			
			
			//copy config file:
			if(!copy($this->pathConfigFile, $this->pathUpdate .Paths::SUB_PATH_SERVER_UPDATE_FILES . Paths::SUB_PATH_CONFIG)) {
				throw new CriticalException('Could not copy settings to update.');
			}
		}
		catch(Throwable $error) {
			$this->revert($error);
		}
		
		return [];
	}
}