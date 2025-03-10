<?php

namespace backend\admin\features\issueFallbackTokensPermission;

use backend\admin\HasIssueFallbackTokensPermission;
use backend\Configs;
use backend\Permission;

class GetInboundFallbackTokens extends HasIssueFallbackTokensPermission
{
	function exec(): array
	{
		if (Permission::isAdmin()) {
			return Configs::getDataStore()->getFallbackTokenStore()->getInboundTokens();
		} else {
			return Configs::getDataStore()->getFallbackTokenStore()->getInboundTokensInfoForUser(Permission::getAccountName());
		}
	}
}