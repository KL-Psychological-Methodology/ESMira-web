<?php

namespace backend\fallback;

use backend\Configs;
use backend\exceptions\FallbackFeatureException;
use backend\FallbackRequestOutput;

abstract class FallbackFeature extends BaseFallbackFeature
{

	function __construct()
	{
		if (!isset($_POST['fallbackToken']))
			throw new FallbackFeatureException("Missing fallback token.", FallbackFeatureException::KEY_MISSING_FROM_REQUEST);

		$token = $_POST['fallbackToken'];
		if (!Configs::getDataStore()->getFallbackTokenStore()->checkInboundToken($token))
			throw new FallbackFeatureException("Fallback token not found.", FallbackFeatureException::KEY_NOT_FOUND);
	}

	abstract function exec(): array;
}
