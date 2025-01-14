<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Configs;

class GetOutboundFallbackUrls extends HasWritePermission
{
	function exec(): array
	{
		$tokenStore = Configs::getDataStore()->getFallbackTokenStore();
		return $tokenStore->getOutboundTokenEncodedUrls();
	}
}
