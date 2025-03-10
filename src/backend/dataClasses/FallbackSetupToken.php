<?php

namespace backend\dataClasses;

class FallbackSetupToken
{
	public $hashedToken;
	public $issuingUser;
	public $creationTime;

	public function __construct(string $hashedToken, string $issuingUser, int $creationTime)
	{
		$this->hashedToken = $hashedToken;
		$this->issuingUser = $issuingUser;
		$this->creationTime = $creationTime;
	}
}
