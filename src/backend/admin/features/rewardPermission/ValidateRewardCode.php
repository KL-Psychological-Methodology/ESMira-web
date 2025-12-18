<?php

namespace backend\admin\features\rewardPermission;

use backend\admin\HasRewardPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\NoRewardCodeException;
use backend\exceptions\PageFlowException;
use backend\JsonOutput;

class ValidateRewardCode extends HasRewardPermission {
	public function execAndOutput() {
		if(!isset($_POST['code']))
			throw new PageFlowException('Missing data');
		
		$code = $_POST['code'];
		try {
			$output = Configs::getDataStore()->getRewardCodeStore()->getRewardCodeData($this->studyId, $code);
		}
		catch(NoRewardCodeException $e) {
			$output = ['faultyCode' => true];
		}
		echo JsonOutput::successObj($output);
	}
	function exec(): array {
		throw new CriticalException('Internal error. GetError can only be used with execAndOutput()');
	}
}