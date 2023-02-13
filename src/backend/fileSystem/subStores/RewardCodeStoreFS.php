<?php

namespace backend\fileSystem\subStores;

use backend\dataClasses\RewardCodeData;
use backend\exceptions\CriticalException;
use backend\fileSystem\loader\RewardCodeDataLoader;
use backend\fileSystem\PathsFS;
use backend\Paths;
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
	
	public function listRewardCodes(int $studyId): array {
		$path = PathsFS::folderRewardCodes($studyId);
		
		if(!file_exists($path))
			throw new CriticalException("Study $studyId does not exist");
		
		$rewardCodes = [];
		$handle = opendir($path);
		while($name = readdir($handle)) {
			if($name[0] != '.')
				$rewardCodes[] = Paths::getFromUrlFriendly($name);
		}
		closedir($handle);
		
		return $rewardCodes;
	}
}