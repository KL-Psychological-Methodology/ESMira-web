<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use Throwable;


interface SnapshotStore {
	
	/**
	 * @throws Throwable
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
    public function createSnapshot();

    public function getSnapshotInfo(): array;

    public function deleteSnapshot();

    public function getSnapshotZipPath(): string;
	
	/**
	 * @throws Throwable
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
    public function restoreSnapshot();

    public function storeUploadPart(string $path, string $name);
    
    public function completeUpload(string $name);

    public function clearUploads(string $curentName);

    public function storeOldConfigs();
	
    public function restoreOldConfigs();
}