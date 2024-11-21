<?php

namespace backend;

use backend\exceptions\CriticalException;
use backend\exceptions\FallbackRequestException;

class FallbackRequest
{
	const UNKNOWN = 0;
	const OK = 1;
	const KEY_MISSING_FROM_REQUEST = 2;
	const KEY_NOT_FOUND = 3;
	const REMOTE_ERROR = 4;
	const UNEXPECTED_REQUEST = 5;

	public function getRequest(string $url, string $feature, array $data): array
	{
		return $this->doRequest($url, $feature, $data, "GET");
	}

	public function postRequest(string $url, string $feature, array $data): array
	{
		return $this->doRequest($url, $feature, $data, "POST");
	}

	private function doRequest(string $url, string $feature, array $data, string $method): array
	{
		$tokenStore = Configs::getDataStore()->getFallbackTokenStore();

		if (!$tokenStore->hasOutboundTokenUrl($url))
			throw new FallbackRequestException("No token registered for given URL.", FallbackRequestException::URL_NOT_REGISTERED);

		$token = $tokenStore->getOutboundTokenForUrl($url);

		$data["fallbackToken"] = $token;

		$options = [
			"http" => [
				"method" => $method,
				"header" => "Content-type: application/x-www-form-urlencoded",
				"content" => http_build_query($data)
			]
		];

		$context = stream_context_create($options);

		$requestURL = $url . "/api/fallback.php?type=" . $feature;

		$response = file_get_contents($requestURL, false, $context);

		if ($response === false)
			throw new FallbackRequestException("No response from server.", FallbackRequestException::NO_RESPONSE);

		$responseData = json_decode($response);

		if (!isset($responseData['code']))
			throw new FallbackRequestException("Response code missing from fallback response.", FallbackRequestException::MISSING_RESPONSE_CODE);

		$responseCode = $responseData['code'];
		switch ($responseCode) {
			case self::KEY_NOT_FOUND:
				throw new FallbackRequestException("Registered key does not exist on target fallback server.", FallbackRequestException::NOT_AUTHORIZED);
				break;
			case self::KEY_MISSING_FROM_REQUEST:
				throw new FallbackRequestException("Request did not include fallback token.", FallbackRequestException::NOT_AUTHORIZED);
				break;
			case self::REMOTE_ERROR:
				$error = "";
				if (isset($responseData['error']))
					$error = $responseData['error'];
				throw new FallbackRequestException("Fallback server encountered an error:\n$error", FallbackRequestException::REMOTE_ERROR);
				break;
			case self::UNEXPECTED_REQUEST:
				throw new FallbackRequestException("Request unexpected by fallback server.", FallbackRequestException::UNEXPECTED_REQUEST);
			case self::OK:
				if (!isset($responseData['dataset']))
					throw new FallbackRequestException("Fallback response is missing response data.", FallbackRequestException::MISSING_RESPONSE_DATA);
				return $responseData['dataset'];
				break;
		}

		throw new FallbackRequestException("Unknown Fallback response code $responseCode", FallbackRequestException::MISSING_RESPONSE_CODE);
	}
}
