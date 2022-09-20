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
		
		Configs::getDataStore()->getAccountStore()->removeAccount($accountName);
		
		return [];
	}
}