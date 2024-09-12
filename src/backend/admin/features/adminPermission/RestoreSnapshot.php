<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\BackupManager;
use backend\Configs;
use backend\exceptions\PageFlowException;
use Throwable;

class restoreSnapshot extends HasAdminPermission {
    function exec(): array {

        $backupManager = new BackupManager();
        
        try {
            $backupManager->moveEverythingToBackupLocation(true);
        } catch (Throwable $e) {
            $backupManager->revertFromBackup($e->getMessage());
            $backupManager->deleteBackup();
            $msg = $e->getMessage();
            throw new PageFlowException("Could not create backup. Error: $msg");
        }

        try {
            $snapshotStore = Configs::getDataStore()->getSnapshotStore();
            $snapshotStore->restoreSnapshot();
        } catch (Throwable $e) {
            throw $backupManager->revertFromBackup($e->getMessage());
        }

        return [];
    }
}