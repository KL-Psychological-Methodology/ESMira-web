<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\FileSystemBasics;
use backend\Paths;
use backend\SSE;
use Throwable;

/**
 * Call order:
 * RestoreSnapshotPrepare -> UpdateStepReplace -> RestoreSnapshotData
 */
class RestoreSnapshotData extends HasAdminPermission {
	private SSE $sse;
	protected string $pathUpdate;
	protected string $pathBackup;
	
	/**
	 * Constructor is only needed for testing.
	 * @param SSE|null $sse SSE object to use for sending progress updates (only used for testing).
	 * @param string $pathUpdate Path to the folder with the update files (only used for testing).
	 * @param string $pathBackup Path to the temporary folder where existing files should be moved to (only used for testing).
	 * @throws CriticalException
	 * @throws PageFlowException
	 */
	public function __construct(?SSE $sse = null, string $pathUpdate = Paths::FOLDER_SERVER_UPDATE, string $pathBackup = Paths::FOLDER_SERVER_BACKUP) {
		parent::__construct();
		$this->sse = $sse ?? new SSE();
		$this->pathUpdate = $pathUpdate;
		$this->pathBackup = $pathBackup;
	}
	
	public function execAndOutput() {
		$this->sse->sendHeader();
		try {
			$snapshotStore = Configs::getDataStore()->getSnapshotStore();
			$snapshotStore->restoreDataFromSnapshot($this->pathUpdate, $this->pathBackup, function(int $stage, int $maxStages, int $step, int $total) {
				$this->sse->flushProgress($stage, $maxStages, $step, $total);
			});
			$this->sse->flushFinished();
		}
		catch(Throwable $e) {
			$this->sse->flushFailed('Could only restore the server files but not the data from the snapshot! ' .$e->getMessage());
		}
		finally {
			Configs::getDataStore()->setMaintenanceMode(false);
			if(file_exists($this->pathUpdate) && FileSystemBasics::isDirEmpty($this->pathUpdate)) {
				rmdir($this->pathUpdate);
			}
			if(file_exists($this->pathBackup) && FileSystemBasics::isDirEmpty($this->pathBackup)) {
				rmdir($this->pathBackup);
			}
		}
	}
	
	function exec(): array {
		throw new CriticalException('Internal error. RestoreSnapshotData can only be used with execAndOutput()');
	}
	
	protected function isReady(): bool {
		return true;
	}
}