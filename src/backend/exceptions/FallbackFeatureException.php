<?php

namespace backend\exceptions;

use Exception;

// For use in incoming fallback requests
class FallbackFeatureException extends Exception
{
	const KEY_MISSING_FROM_REQUEST = 1;
	const DATA_MISSING_FROM_REQUEST = 2;
	const KEY_NOT_FOUND = 3;

	public function __construct(string $messag, int $code)
	{
		parent::__construct($messag, $code);
	}
}