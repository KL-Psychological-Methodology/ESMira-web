<?php

namespace backend\admin\features\issueFallbackTokensPermission;

use backend\admin\HasIssueFallbackTokensPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\Permission;

class DeleteInboundFallbackToken extends HasIssueFallbackTokensPermission
{
	function exec(): array
	{
		if (!isset($_POST['url']) || !isset($_POST['user']))
			throw new PageFlowException('Missing data');
		$user = $_POST['user'];
		$url = base64_decode($_POST['url']);

		if (strcmp(Permission::getAccountName(), $user) !== 0 && !Permission::isAdmin())
			throw new CriticalException("No Permission");

		Configs::getDataStore()->getFallbackTokenStore()->deleteInboundToken($user, $url);
		return [];
	}
}