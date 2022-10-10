<?php

namespace backend\fileSystem\loader;


use backend\dataClasses\RewardCodeData;
use backend\exceptions\CriticalException;
use backend\exceptions\NoRewardCodeException;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;

class RewardCodeDataLoader {
	/**
	 * @throws NoRewardCodeException
	 */
	public static function importFile(int $studyId, string $code): RewardCodeData {
		$path = PathsFS::fileRewardCode($studyId, $code);
		if(empty($code) || !file_exists($path))
			throw new NoRewardCodeException('Code does not exist', NoRewardCodeException::DOES_NOT_EXIST);
		return unserialize(file_get_contents($path));
	}
	
	/**
	 * @throws CriticalException
	 */
	public static function exportFile(int $studyId, RewardCodeData $rewardCodeData) {
		$folderPath = PathsFS::folderRewardCodes($studyId);
		$filePath = PathsFS::fileRewardCode($studyId, $rewardCodeData->code);
		if(!file_exists($folderPath))
			FileSystemBasics::createFolder($folderPath);
		FileSystemBasics::writeFile($filePath, serialize($rewardCodeData));
	}
}