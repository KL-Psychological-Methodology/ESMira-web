<?php

namespace backend\dataClasses;

class TokenInfo {
	/**
	 * @var string
	 */
	public $tokenId;
	/**
	 * @var int
	 */
	public $lastUsed;
	/**
	 * @var bool
	 */
	public $current;
	
	public function __construct(string $tokenId, int $lastUsed, bool $current) {
		$this->tokenId = $tokenId;
		$this->lastUsed = $lastUsed;
		$this->current = $current;
	}
}