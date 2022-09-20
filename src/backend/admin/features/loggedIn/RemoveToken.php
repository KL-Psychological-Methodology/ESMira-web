<?php

namespace backend\admin\features\loggedIn;

use backend\admin\features\loggedIn\GetTokenList;
use backend\admin\IsLoggedIn;
use backend\Configs;
use backend\exceptions\PageFlowException;
use backend\Permission;

class RemoveToken extends IsLoggedIn {
	
	function exec(): array {
		if(!isset($_POST['token_id']))
			throw new PageFlowException('Missing data');
		
		$accountName = Permission::getAccountName();
		$tokenId = $_POST['token_id'];
		Configs::getDataStore()->getLoginTokenStore()->removeLoginToken($accountName, $tokenId);
		
		$c = new GetTokenList();
		return $c->exec();
	}
}