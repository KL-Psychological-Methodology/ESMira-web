<?php

namespace backend\dataClasses;

class UserData {
	/**
	 * @var int
	 */
	public $userIdInteger;
	
	/**
	 * @var string
	 */
	public $token;
	
	/**
	 * @var int
	 */
	public $dataSetCount;
	
	/**
	 * @var int
	 */
	public $group;
	
	/**
	 * @var string
	 */
	public $appType;
	
	/**
	 * @var string
	 */
	public $appVersion;
	
	public function __construct(int $userIdInteger, int $token, int $dataSetCount, int $group, string $appType, string $appVersion) {
		$this->userIdInteger = $userIdInteger;
		$this->token = $token;
		$this->dataSetCount = $dataSetCount;
		$this->group = $group;
		$this->appType = $appType;
		$this->appVersion = $appVersion;
	}
}