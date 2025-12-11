<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\fileSystem\PathsFS;

class DeleteSnapshot extends HasAdminPermission {
    
    function exec(): array {
		$snapshotName = $_POST['snapshotName'];
		$pathZip = PathsFS::fileSnapshotZip($snapshotName);
		unlink($pathZip);
		
        return [];
    }

}