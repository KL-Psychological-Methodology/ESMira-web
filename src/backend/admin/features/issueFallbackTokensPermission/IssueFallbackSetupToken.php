<?php

namespace backend\admin\features\issueFallbackTokensPermission;

use backend\admin\HasIssueFallbackTokensPermission;
use backend\Configs;
use backend\Permission;

class IssueFallbackSetupToken extends HasIssueFallbackTokensPermission
{
	function exec(): array
	{
		$accountName = Permission::getAccountName();
		$token = Configs::getDataStore()->getFallbackTokenStore()->issueSetupToken($accountName);
		return [$token];
	}
}