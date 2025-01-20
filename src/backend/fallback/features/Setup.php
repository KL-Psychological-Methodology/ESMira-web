<?php

namespace backend\fallback\features;

use backend\Configs;
use backend\exceptions\FallbackFeatureException;
use backend\fallback\BaseFallbackFeature;

class Setup extends BaseFallbackFeature
{
	function exec(): array
	{
		if (!isset($_POST['setupToken']) || !isset($_POST['encodedUrl']))
			throw new FallbackFeatureException("Missing data.", FallbackFeatureException::DATA_MISSING_FROM_REQUEST);

		$setupToken = $_POST['setupToken'];
		$encodedUrl = $_POST['encodedUrl'];

		$fallbackToken = Configs::getDataStore()->getFallbackTokenStore()->issueInboundToken($setupToken, $encodedUrl);

		return ["fallbackToken" => $fallbackToken];
	}
}