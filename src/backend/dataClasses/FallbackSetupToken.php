<?php

namespace backend\dataClasses;

class FallbackSetupToken
{
	public $tokenHash;
	public $issuingUser;
	public $creationTime;

	public function __construct(string $tokenHash, string $issuingUser, int $creationTime)
	{
		$this->$tokenHash = $tokenHash;
		$this->issuingUser = $issuingUser;
		$this->creationTime = $creationTime;
	}
}