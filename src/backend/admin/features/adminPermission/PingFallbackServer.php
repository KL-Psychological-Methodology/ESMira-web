<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\FallbackRequestException;
use backend\exceptions\PageFlowException;
use backend\FallbackRequest;

class PingFallbackServer extends HasAdminPermission
{
	function exec(): array
	{
		if (!isset($_POST['url']))
			throw new PageFlowException("Missing data");

		$rawUrl = $_POST['url'];
		$url = base64_decode($rawUrl);

		$request = new FallbackRequest();
		try {
			$request->postRequest($url, "Ping", []);
		} catch (FallbackRequestException $e) {
			throw new CriticalException($e->getMessage());
		}
		return [];
	}
}
