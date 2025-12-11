<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;

class ListSnapshots extends HasAdminPermission {
    function exec(): array {
        $snapshotStore = Configs::getDataStore()->getSnapshotStore();
        return $snapshotStore->listSnapshots();
    }
}