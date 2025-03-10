<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\PageFlowException;

class SetOutboundFallbackTokensList extends HasAdminPermission
{
	function exec(): array
	{
		if (!isset($_POST['urlList']))
			throw new PageFlowException("Missing data");

		$rawUrlList = json_decode($_POST['urlList']);
		$urlList = [];
		foreach ($rawUrlList as $rawUrl)
			$urlList[] = base64_decode($rawUrl);

		Configs::getDataStore()->getFallbackTokenStore()->setOutboundTokensList($urlList);

		return [];
	}
}
