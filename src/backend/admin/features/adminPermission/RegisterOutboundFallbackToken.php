<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\PageFlowException;

class RegisterOutboundFallbackToken extends HasAdminPermission
{
	function exec(): array
	{
		if (!isset($_POST['url']) || !isset($_POST['token']))
			throw new PageFlowException('Missing data');

		$url = base64_decode($_POST['url']);
		$token = $_POST['token'];

		Configs::getDataStore()->getFallbackTokenStore()->registerOutboundToken($url, $token);
		return [];
	}
}