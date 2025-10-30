<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;

class DeletePlugin extends HasAdminPermission {
    function exec(): array {
        $dataStore = Configs::getDataStore();
        $pluginsStore = $dataStore->getPluginStore();
		
		if(isset($_POST['pluginName'])) {
			$pluginsStore->deletePlugin($_POST['pluginName']);
		}
		
		return [];
    }
}
