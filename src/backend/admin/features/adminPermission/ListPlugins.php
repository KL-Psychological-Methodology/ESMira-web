<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;

class ListPlugins extends HasAdminPermission {
    function exec(): array {
        $dataStore = Configs::getDataStore();
        $pluginsStore = $dataStore->getPluginStore();
		
		return $pluginsStore->getPluginList();
    }
}
