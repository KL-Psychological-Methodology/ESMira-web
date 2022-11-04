<?php

namespace backend\exceptions;

use Exception;

class NoRewardCodeException extends Exception {
	const DOES_NOT_EXIST = 1;
	const NOT_ENABLED = 2;
	const UNFULFILLED_REWARD_CONDITIONS = 3;
	const ALREADY_GENERATED = 4;
	
	/**
	 * @var array
	 */
	private $fulfilledQuestionnaires;
	
	public function __construct(string $message, int $code, array $fulfilledQuestionnaires = []) {
		parent::__construct($message, $code);
		$this->fulfilledQuestionnaires = $fulfilledQuestionnaires;
	}
	
	public function getFulfilledQuestionnaires(): array {
		return $this->fulfilledQuestionnaires;
	}
}