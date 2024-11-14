<?php

namespace backend\admin\features\issueFallbackTokensPermission;

use backend\admin\HasIssueFallbackTokensPermission;
use backend\Configs;
use backend\exceptions\PageFlowException;
use backend\Permission;

class IssueInboundFallbackToken extends HasIssueFallbackTokensPermission
{
	function exec(): array
	{
		if (!isset($_POST['url']))
			throw new PageFlowException('Missing data');
		$url = base64_decode($_POST['url']);
		$accountName = Permission::getAccountName();
		$token = Configs::getDataStore()->getFallbackTokenStore()->issueInboundToken($accountName, $url);
		return [$token];
	}
}