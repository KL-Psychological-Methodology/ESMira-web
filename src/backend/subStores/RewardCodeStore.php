<?php

namespace backend\subStores;

use backend\dataClasses\RewardCodeData;
use backend\exceptions\CriticalException;
use backend\exceptions\NoRewardCodeException;

interface RewardCodeStore {
	public function hasRewardCode(int $studyId, string $code): bool;
	
	/**
	 * @throws NoRewardCodeException
	 */
	public function getRewardCodeData(int $studyId, string $code): RewardCodeData;
	
	/**
	 * @throws CriticalException
	 */
	public function saveRewardCodeData(int $studyId, RewardCodeData $rewardCodeData);
	
	/**
	 * @throws CriticalException
	 */
	function listRewardCodes(int $studyId): array;
}