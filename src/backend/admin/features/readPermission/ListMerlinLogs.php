<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Configs;

class ListMerlinLogs extends HasReadPermission {
    function exec(): array {
        return Configs::getDataStore()->getMerlinLogsStore()->getMerlinLogsList($this->studyId);
    }
}