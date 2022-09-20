<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\PageFlowException;

class CreateAccount extends HasAdminPermission {
	
	function exec(): array {
		if(!isset($_POST['new_account']) || !isset($_POST['pass']) || strlen($_POST['pass']) <= 3)
			throw new PageFlowException('Missing data');
		
		$accountName = $_POST['new_account'];
		$pass = $_POST['pass'];
		$accountStore = Configs::getDataStore()->getAccountStore();
		if($accountStore->doesAccountExist($accountName))
			throw new PageFlowException("Account name '$accountName' already exists");
		
		
		$accountStore->setAccount($accountName, $pass);
		return ['accountName' => $accountName];
	}
}