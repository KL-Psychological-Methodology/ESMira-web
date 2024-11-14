<?php

namespace backend\admin\features\issueFallbackTokensPermission;

use backend\admin\HasIssueFallbackTokensPermission;
use backend\Configs;
use backend\Permission;

class GetInboundFallbackTokensForUser extends HasIssueFallbackTokensPermission
{
	function exec(): array
	{
		$accountName = Permission::getAccountName();
		return Configs::getDataStore()->getFallbackTokenStore()->getInboundTokensInfoForUser($accountName);
	}
}
