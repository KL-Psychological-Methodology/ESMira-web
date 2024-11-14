<?php

namespace backend\dataClasses;

/*
 * This class stores a (hashed) token issued by this ESMira server,
 * which allows another server to use this serveras a fallback.
 */

class InboundFallbackToken
{
	/**
	 * @var string
	 */
	public $hashedToken;
	/**
	 * @var string
	 */
	public $otherServerUrl;

	public function __construct(string $url, string $hashedToken)
	{
		$this->hashedToken = $hashedToken;
		$this->otherServerUrl = $url;
	}
}