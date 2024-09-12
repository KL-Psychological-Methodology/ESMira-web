<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\fileSystem\PathsFS;
use backend\Paths;
use backend\Main;

class GetSnapshot extends HasAdminPermission {
    
    function execAndOutput()
    {
        $snapshotStore = Configs::getDataStore()->getSnapshotStore();
        if(!$snapshotStore->getSnapshotInfo()["hasSnapshot"]) {
            throw new PageFlowException('No snapshot present.');
        }
        
        $chunksize = 5 * (1024 * 1024); //5 MB (= 5 242 880 bytes) per one chunk of file.
        $pathSnapshot = $snapshotStore->getSnapshotZipPath();
        $size = intval(sprintf("%u", filesize($pathSnapshot)));

        Main::setHeader('Cache-Control: no-cache, must-revalidate');
		Main::setHeader('Content-Type: application/octet-stream');
		Main::setHeader('Content-Disposition: attachment; filename=' . Paths::FILENAME_SNAPSHOT_ZIP);
		Main::setHeader('Content-Transfer-Encoding: binary');
		Main::setHeader('Content-Length: ' . filesize($pathSnapshot));
	
        if($size > $chunksize) {
            $handle = fopen($pathSnapshot, 'rb');
            while(!feof($handle)) {
                print(@fread($handle, $chunksize));
                ob_flush();
                flush();
            }
            fclose($handle);
        } else readfile($pathSnapshot);
    }

    function exec(): array {
        throw new CriticalException('Internal error. GetSnaphsot can only be used with execAndOutput()');
    }
}