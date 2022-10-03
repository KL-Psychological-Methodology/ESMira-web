<?php

namespace backend\fileSystem\subStores;

use backend\dataClasses\RewardCodeData;
use backend\fileSystem\loader\RewardCodeDataLoader;
use backend\fileSystem\PathsFS;
use backend\subStores\RewardCodeStore;

class RewardCodeStoreFS implements RewardCodeStore {
	
	public function hasRewardCode(int $studyId, string $code): bool {
		return file_exists(PathsFS::fileRewardCode($studyId, $code));
	}
	
	public function getRewardCodeData(int $studyId, string $code): RewardCodeData {
		return RewardCodeDataLoader::importFile($studyId, $code);
	}
	
	public function saveRewardCodeData(int $studyId, RewardCodeData $rewardCodeData) {
		RewardCodeDataLoader::exportFile($studyId, $rewardCodeData);
	}
}