<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\SnapshotManager;

class DeleteSnapshot extends HasAdminPermission {
    
    function exec(): array {
        Configs::getDataStore()->getSnapshotStore()->deleteSnapshot();
        return [];
    }

}