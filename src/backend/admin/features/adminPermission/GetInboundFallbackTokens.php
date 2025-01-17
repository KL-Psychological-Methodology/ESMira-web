<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;

class GetInboundFallbackTokens extends HasAdminPermission
{
	function exec(): array
	{
		return Configs::getDataStore()->getFallbackTokenStore()->getInboundTokens();
	}
}