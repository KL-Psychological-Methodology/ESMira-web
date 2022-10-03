<?php

namespace backend\exceptions;

use Exception;

class NoRewardCodeException extends Exception {
	const DOES_NOT_EXIST = 1;
	const NOT_ENABLED = 2;
	const UNFULFILLED_REWARD_CONDITIONS = 3;
	const ALREADY_GENERATED = 4;
}