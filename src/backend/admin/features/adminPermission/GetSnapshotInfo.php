<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\JsonOutput;

class GetSnapshotInfo extends HasAdminPermission {    
    function exec(): array {
        $snapshotStore = Configs::getDataStore()->getSnapshotStore();
        return $snapshotStore->getSnapshotInfo();
    }
}