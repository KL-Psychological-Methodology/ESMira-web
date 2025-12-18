<?php

namespace backend\admin\features\rewardPermission;

use backend\admin\HasRewardPermission;
use backend\Configs;

class GetRewardCodeData extends HasRewardPermission {
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