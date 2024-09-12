<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;


interface SnapshotStore {
    /**
     * @throws CriticalException
     */
    public function createSnapshot();

    public function getSnapshotInfo(): array;

    public function deleteSnapshot();

    public function getSnapshotZipPath(): string;

    /**
     * @throws CriticalException
     */
    public function restoreSnapshot();

    public function storeUploadPart(string $path, string $name);
    
    public function completeUpload(string $name);

    public function clearUploads(string $curentName);
}