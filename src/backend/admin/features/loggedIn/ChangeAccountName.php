<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Main;
use backend\Configs;
use backend\exceptions\PageFlowException;
use backend\Permission;

class ChangeAccountName extends IsLoggedIn {
	
	function exec(): array {
		if(!isset($_POST['new_account']))
			throw new PageFlowException('Missing data');
		
		$dataStore = Configs::getDataStore();
		$accountStore = $dataStore->getAccountStore();
		$bookmarksStore = $dataStore->getBookmarkStore();		

		if($this->isAdmin && isset($_POST['accountName'])) {
			$accountName = $_POST['accountName'];
			if(!$accountStore->doesAccountExist($accountName))
				throw new PageFlowException("Account $accountName does not exist");
		}
		else
			$accountName = Permission::getAccountName();
		
		$newAccountName = $_POST['new_account'];
		
		if(strlen($newAccountName) < 3)
			throw new PageFlowException("Username needs to contain at least 3 characters");
		else if($accountStore->doesAccountExist($newAccountName))
			throw new PageFlowException("Username '$newAccountName' already exists");
		
		$accountStore->changeAccountName($accountName, $newAccountName);
		$bookmarksStore->changeUser($accountName, $newAccountName);

		if(Permission::getAccountName() == $accountName) {
			$_SESSION['account'] = $newAccountName;
			if(isset($_COOKIE['account']))
				Main::setCookie('account', $newAccountName, time()+31536000);
		}
		
		return [];
	}
}