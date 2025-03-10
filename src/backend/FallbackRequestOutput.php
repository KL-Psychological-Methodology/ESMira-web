<?php

namespace backend;

class FallbackRequestOutput extends JsonOutput
{
	static function getBaseResponse(bool $success, int $code): array
	{
		return [
			'success' => $success,
			'serverVersion' => Main::SERVER_VERSION,
			'code' => $code,
		];
	}

	static function error(string $string, int $errorCode = 0): string
	{
		self::doHeaders();
		$response = self::getBaseResponse(false, FallbackRequest::REMOTE_ERROR);
		$response['error'] = $string;
		$response['errorCode'] = $errorCode;
		return json_encode($response);
	}

	static function noSuccess(int $code): string
	{
		self::doHeaders();
		$response = self::getBaseResponse(false, $code);
		return json_encode($response);
	}


	static function successString(string $s = '1'): string
	{
		self::doHeaders();
		$response = self::getBaseResponse(true, FallbackRequest::OK);
		$response['dataset'] = $s;
		return json_encode($response);
	}

	static function successObj($obj = true): string
	{
		self::doHeaders();
		$response = self::getBaseResponse(true, FallbackRequest::OK);
		$response['dataset'] = $obj;
		return json_encode($response);
	}
}
