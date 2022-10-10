<?php

namespace backend\dataClasses;

use backend\Main;

class UserData {
	/**
	 * @var int
	 */
	public $joinedTime;
	
	/**
	 * @var int
	 */
	public $lastDataSetTime = -1;
	
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
	
	public $group = 0;
	public $appType = '';
	public $appVersion = '';
	
	public $questionnaireDataSetCount = [];
	
	public $generatedRewardCode = false;
	
	public function __construct(int $userIdInteger, int $token, int $dataSetCount) {
		$this->userIdInteger = $userIdInteger;
		$this->token = $token;
		$this->dataSetCount = $dataSetCount;
		$this->joinedTime = Main::getMilliseconds();
	}
}