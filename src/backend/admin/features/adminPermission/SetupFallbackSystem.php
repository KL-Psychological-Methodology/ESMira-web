<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\FallbackRequestException;
use backend\exceptions\PageFlowException;
use backend\FallbackRequest;

class SetupFallbackSystem extends HasAdminPermission
{
	function exec(): array
	{
		if (!isset($_POST['ownUrl']) || !isset($_POST['otherUrl']) || !isset($_POST['setupToken']))
			throw new PageFlowException("Missing data");

		$ownUrl = $_POST['ownUrl'];
		$encodedOtherUrl = $_POST['ohterUrl'];
		$otherUrl = base64_decode($encodedOtherUrl);
		$setupToken = $_POST['setupToken'];

		$request = new FallbackRequest();
		try {
			$response = $request->postRequest($otherUrl, "Setup", [
				"encodedUrl" => $ownUrl,
				"setupToken" => $setupToken,
			]);

			if (!isset($response['fallbackToken']))
				throw new CriticalException("No fallback token included in remote server response");

			$fallbackToken = $response['fallbackToken'];
			Configs::getDataStore()->getFallbackTokenStore()->registerOutboundToken($encodedOtherUrl, $fallbackToken);
		} catch (FallbackRequestException $e) {
			throw new CriticalException($e->getMessage());
		}

		return [];
	}
}