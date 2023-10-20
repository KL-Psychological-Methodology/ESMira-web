<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\NoRewardCodeException;
use backend\exceptions\PageFlowException;
use backend\JsonOutput;

class GetRewardCodeData extends HasReadPermission {
	function exec(): array {
		$dataStore = Configs::getDataStore();
		
		$rewardCodes = $dataStore->getRewardCodeStore()->listRewardCodes($this->studyId);
		$participants = $dataStore->getStudyStore()->getStudyParticipants($this->studyId);
		$participantsWithRewardCode = [];
		$participantsWithoutRewardCode = [];
		
		foreach($participants as $userId) {
			$userData = $dataStore->getUserDataStore($userId)->getUserData($this->studyId);
			if($userData->generatedRewardCode)
				$participantsWithRewardCode[] = $userId;
			else
				$participantsWithoutRewardCode[] = $userId;
			
		}
		
		return [
			'rewardCodes' => $rewardCodes,
			'userIdsWithRewardCode' => $participantsWithRewardCode,
			'userIdsWithoutRewardCode' => $participantsWithoutRewardCode
		];
	}
}