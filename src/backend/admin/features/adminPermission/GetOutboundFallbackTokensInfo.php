<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;

class GetOutboundFallbackTokensInfo extends HasAdminPermission
{
	function exec(): array
	{
		$tokenInfos = Configs::getDataStore()->getFallbackTokenStore()->getOutboundTokenUrls();
		return $tokenInfos;
	}
}
