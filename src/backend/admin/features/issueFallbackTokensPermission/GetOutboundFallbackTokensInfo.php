<?php

namespace backend\admin\features\issueFallbackTokensPermission;

use backend\admin\HasIssueFallbackTokensPermission;
use backend\Configs;
use backend\Permission;

/*
* This is called at load time in the fallback system page, therefore needs to be accessible to all users with access to that page.
* However, this Information itself should only be accessible to Admins. Therefore this script needs to extend HasIssueFallbackTokensPermission, but is
* actually "Pseudo-HasAdminPermission"
*/

class GetOutboundFallbackTokensInfo extends HasIssueFallbackTokensPermission
{
	function exec(): array
	{
		if (Permission::isAdmin()) {
			$tokenInfos = Configs::getDataStore()->getFallbackTokenStore()->getOutboundTokenUrls();
		} else {
			$tokenInfos = [];
		}
		return $tokenInfos;
	}
}
