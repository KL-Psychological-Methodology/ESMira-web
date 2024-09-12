<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;
use ZipArchive;

interface DataBackupStore {
    /**
     * @throws CriticalException
     */
    public function backupData();

    /**
     * @throws CriticalException
     */
    public function restoreData();

    public function deleteBackup();

}