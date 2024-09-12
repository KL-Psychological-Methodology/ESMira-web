<?php

namespace backend\fileSystem\subStores;

use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\subStores\DataBackupStore;
use ZipArchive;

class DataBackupStoreFS implements DataBackupStore {
    /**
     * @throws CriticalException
     */
    public function backupData(){
        $backupPath = PathsFS::folderDataBackup();
        
        if(self::backupExists())
            self::deleteBackup();
        FileSystemBasics::createFolder($backupPath);
        $folderData = PathsFS::folderData();
        $exclude = [
            $backupPath,
            PathsFS::folderDataBackup()
        ];
        $this->copyRecursively($folderData, $backupPath, $exclude);
    }

    /**
     * @throws CriticalException
     */
    public function restoreData(){
        $this->cleanDataDirectory();
        $dataPath = PathsFS::folderData();
        $backupPath = PathsFS::folderDataBackup();
        $this->copyRecursively($backupPath, $dataPath);
        $this->deleteBackup();
    }

    public function deleteBackup(){
        unlink(PathsFS::folderDataBackup());
    }

    private function backupExists(): bool {
        return file_exists(PathsFS::folderDataBackup());
    }

    private function copyRecursively(string $sourceDirectory, string $destinationDirectory, array $excludeTopLevel = []) {
        $handle = opendir($sourceDirectory);

        @mkdir($destinationDirectory, 0744);

        while($file = readdir($handle)) {
            if($file == '.' || $file == '..' || in_array($file, $excludeTopLevel))
                continue;

            if(is_dir($sourceDirectory . '/' . $file)) {
                $this->copyRecursively($sourceDirectory . '/' . $file, $destinationDirectory . '/' . $file);
            } else {
                copy($sourceDirectory . '/' . $file, $destinationDirectory . '/' . $file);
            }
        }
        closedir($handle);
    }

    private function cleanDataDirectory() {
        $backupPath = PathsFS::folderDataBackup();
        $dataPath = PathsFS::folderData();
        $handle = opendir($dataPath);
        while($file = readdir($handle)) {
            if($file == '.' || $file == '..' || $file == basename($backupPath))
                continue;
            unlink($dataPath . '/' . $file);
        }
        closedir($handle);
    }
}