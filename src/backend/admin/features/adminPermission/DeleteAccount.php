<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\PageFlowException;

class DeleteAccount extends HasAdminPermission {
	
	function exec(): array {
		if(!isset($_POST['accountName']))
			throw new PageFlowException('Missing data');
		
		$accountName = $_POST['accountName'];
		
		$dataStore = Configs::getDataStore();
		$dataStore->getAccountStore()->removeAccount($accountName);
		$dataStore->getBookmarkStore()->deleteBookmarksUser($accountName);

		return [];
	}
}