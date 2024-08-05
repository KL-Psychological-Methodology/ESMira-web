<?php

namespace backend\admin\features\noPermission;

use backend\admin\NoPermission;
use backend\Configs;
use backend\fileSystem\PathsFS;
use backend\Permission;

class GetPermissions extends NoPermission {
	
	function exec(): array {
		if(!Permission::isLoggedIn() || !Configs::getDataStore()->isInit())
			return ['isLoggedIn' => false];
		else {
			if(Permission::isAdmin()) {
				$obj = [
					'isAdmin' => true,
					'canCreate' => true,
					'hasErrors' => Configs::getDataStore()->getErrorReportStore()->hasErrorReports(),
					'totalDiskSpace' => disk_total_space(PathsFS::folderData()),
					'freeDiskSpace' => disk_free_space(PathsFS::folderData()),
				];
			}
			else
				$obj = [
					'permissions' => Permission::getPermissions(),
					'canCreate' => Permission::canCreate()
				];
			$obj['accountName'] = Permission::getAccountName();
			$obj['isLoggedIn'] = true;
			$obj['newMessages'] = Configs::getDataStore()->getMessagesStore()->getStudiesWithUnreadMessagesForPermission();
			$obj['newMerlinLogs'] = Configs::getDataStore()->getMerlinLogsStore()->getStudiesWithUnreadMerlinLogsForPermission();
			return $obj;
		}
	}
}