<?php

namespace backend\dataClasses;

/*
 * This class stores a token issued by another ESMira server,
 * which this server may use as a fallback.
 */

class OutboundFallbackToken
{
	/**
	 * @var string
	 */
	public $token;
	/**
	 * @var string
	 */
	public $url;

	public function __construct(string $token, string $url)
	{
		$this->token = $token;
		$this->url = $url;
	}
}