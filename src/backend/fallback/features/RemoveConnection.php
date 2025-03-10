<?php

namespace backend\fallback\features;

use backend\Configs;
use backend\fallback\FallbackFeature;

class RemoveConnection extends FallbackFeature
{
	function exec(): array
	{
		$tokenStore = Configs::getDataStore()->getFallbackTokenStore();
		$tokens = $tokenStore->getInboundTokens();
		$accountName = "";
		foreach ($tokens as $token) {
			if (strcmp($token->otherServerUrl, $this->encodedUrl) == 0) {
				$accountName = $token->issuingUser;
				break;
			}
		}
		$tokenStore->deleteInboundToken($accountName, $this->encodedUrl);

		return [];
	}
}