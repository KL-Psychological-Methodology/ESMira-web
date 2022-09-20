<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Configs;
use backend\exceptions\PageFlowException;
use backend\Permission;

class ChangePassword extends IsLoggedIn {
	
	function exec(): array {
		if(!isset($_POST['new_pass']))
			throw new PageFlowException('Missing data');
		
		$accountStore = Configs::getDataStore()->getAccountStore();
		$pass = $_POST['new_pass'];
		
		if($this->isAdmin && isset($_POST['accountName'])) {
			$accountName = $_POST['accountName'];
			if(!$accountStore->doesAccountExist($accountName))
				throw new PageFlowException("Account $accountName does not exist");
		}
		else
			$accountName = Permission::getAccountName();
		
		if(strlen($pass) < 12)
			throw new PageFlowException('The password needs to have at least 12 characters.');
		
		
		$accountStore->setAccount($accountName, $pass);
		
		return [];
	}
}