<?php

namespace backend\exceptions;

use Exception;

// For use with outgoing fallback requests
class FallbackRequestException extends Exception
{
	const URL_NOT_REGISTERED = 1;
	const NO_RESPONSE = 2;
	const NOT_AUTHORIZED = 3;
	const MISSING_RESPONSE_CODE = 4;
	const REMOTE_ERROR = 5;
	const MISSING_RESPONSE_DATA = 6;
	const UNEXPECTED_REQUEST = 7;

	public function __construct(string $message, int $code)
	{
		parent::__construct($message, $code);
	}
}
