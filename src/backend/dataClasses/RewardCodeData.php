<?php

namespace backend\dataClasses;

use backend\exceptions\CriticalException;
use backend\Main;
use backend\Permission;

class RewardCodeData {
	/**
	 * @var int
	 */
	public $timestamp;
	/**
	 * @var string
	 */
	public $code;
	/**
	 * @var array
	 */
	public $questionnaireDataSetCount;
	
	/**
	 * @throws CriticalException
	 */
	public function __construct(array $questionnaireDataSetCount) {
		$this->timestamp = Main::getMilliseconds();
		$this->code = Permission::calcRandomToken(5);
		$this->questionnaireDataSetCount = $questionnaireDataSetCount;
	}
}