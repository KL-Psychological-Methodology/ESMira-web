<?php

namespace backend\dataClasses;

/*
 * This class stores a (hashed) token issued by this ESMira server,
 * which allows another server to use this serveras a fallback.
 */

class InboundFallbackToken
{
	public $hashedToken;
	public $otherServerUrl; // encoded as base64
	public $issuingUser;


	public function __construct(string $url, string $hashedToken, string $issuingUser)
	{
		$this->hashedToken = $hashedToken;
		$this->otherServerUrl = $url;
		$this->issuingUser = $issuingUser;
	}
}