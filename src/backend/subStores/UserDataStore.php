<?php
declare(strict_types=1);

namespace backend\subStores;

use backend\dataClasses\RewardCodeData;
use backend\exceptions\NoRewardCodeException;
use backend\Main;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\dataClasses\UserData;
use stdClass;

const ONE_DAY = 86400000; //in seconds: 1000*60*60*24

abstract class UserDataStore {
	/**
	 * @var string
	 */
	protected $userId;
	
	/**
	 * @var UserData[]
	 */
	protected $userDataArray = [];
	
	/**
	 * @var int
	 */
	protected $newStudyToken;
	
	/**
	 * @var bool[]
	 */
	protected $isNewUser = [];
	
	function __construct(string $userId) {
		$this->userId = $userId;
		$this->newStudyToken = Main::getMilliseconds();
	}
	
	protected function createNewUserData(int $studyId): UserData {
		return new UserData(
			$this->createNewUserIdInteger($studyId),
			$this->newStudyToken,
			0
		);
	}
	
	/**
	 * @throws CriticalException
	 */
	public function addDataSetForSaving(int $studyId, stdClass $dataSet, string $appType, string $appVersion): bool {
		$userData = $this->userDataArray[$studyId] ?? $this->getUserDataForWriting($studyId);
		
		$userData->group = $dataSet->group ?? 0;
		$userData->appType = $appType;
		$userData->appVersion = $appVersion;
		
		++$userData->dataSetCount;
		if(($dataSet->eventType ?? '') == 'questionnaire' && ($dataSet->questionnaireInternalId ?? -1) != -1) {
			if(!isset($userData->questionnaireDataSetCount[$dataSet->questionnaireInternalId]))
				$userData->questionnaireDataSetCount[$dataSet->questionnaireInternalId] = 1;
			else
				++$userData->questionnaireDataSetCount[$dataSet->questionnaireInternalId];
		}
		$userData->lastDataSetTime = Main::getMilliseconds();
		
		$currentToken = $userData->token;
		return $this->isNewUser($studyId) || $this->newStudyToken - $currentToken >= Configs::get('dataset_server_timeout');
	}
	
	public function isOutdated(int $studyId, int $sentToken, bool $isReupload): bool {
		if(!$isReupload || $sentToken == 0)
			return false;
		return array_key_exists($studyId, $this->userDataArray) && $sentToken != $this->userDataArray[$studyId]->token;
	}
	
	public function isNewUser(int $studyId): bool {
		return isset($this->isNewUser[$studyId]);
	}
	public function countNewUser(): int {
		return count($this->isNewUser);
	}
	
	public function getNewStudyTokens(): array {
		if(empty($this->userDataArray))
			return [];
		
		$tokens = [];
		foreach($this->userDataArray as $studyId => $userData) {
			$tokens[$studyId] = $this->newStudyToken;
		}
		return $tokens;
	}
	public function getDataSetId(int $studyId): int {
		$userData = $this->userDataArray[$studyId];
		return $userData->userIdInteger * 1000000 + $userData->dataSetCount;
	}
	
	
	/**
	 * @throws CriticalException
	 * @throws NoRewardCodeException
	 */
	public function generateRewardCode(int $studyId): string {
		$userdata = $this->getUserDataForWriting($studyId);
		
		if($userdata->generatedRewardCode) {
			$this->close();
			throw new NoRewardCodeException('Reward code was already generated', NoRewardCodeException::ALREADY_GENERATED);
		}
		
		$study = Configs::getDataStore()->getStudyStore()->getStudyConfig($studyId);
		
		if(!$study->enableRewardSystem) {
			$this->close();
			throw new NoRewardCodeException('Reward codes are disabled', NoRewardCodeException::NOT_ENABLED);
		}
		
		if(Main::getMilliseconds() < $userdata->joinedTime + ONE_DAY * ($study->rewardVisibleAfterDays ?? 0)) {
			$this->close();
			throw new NoRewardCodeException('Rewards are not accessible yet', NoRewardCodeException::UNFULFILLED_REWARD_CONDITIONS);
		}
		
		$unfulfilledQuestionnaires = false;
		$fulfilledQuestionnaires = [];
		foreach($study->questionnaires as $questionnaire) {
			$qId = $questionnaire->internalId;
			$min = $questionnaire->minDataSetsForReward ?? 0;
			if($min != 0 && ($userdata->questionnaireDataSetCount[$qId] ?? 0) < $min) {
				$unfulfilledQuestionnaires = true;
				$fulfilledQuestionnaires[$qId] = false;
			}
			else
				$fulfilledQuestionnaires[$qId] = true;
		}
		
		if($unfulfilledQuestionnaires) {
			$this->close();
			throw new NoRewardCodeException('Not all conditions are fulfilled', NoRewardCodeException::UNFULFILLED_REWARD_CONDITIONS, $fulfilledQuestionnaires);
		}
		
		$rewardCodeStore = Configs::getDataStore()->getRewardCodeStore();
		do {
			$rewardCodeData = new RewardCodeData($userdata->questionnaireDataSetCount);
		} while($rewardCodeStore->hasRewardCode($studyId, $rewardCodeData->code));
		$rewardCodeStore->saveRewardCodeData($studyId, $rewardCodeData);
		
		$userdata->generatedRewardCode = true;
		$this->writeAndClose();
		return $rewardCodeData->code;
	}
	
	/**
	 * @throws CriticalException
	 */
	abstract protected function getUserDataForWriting(int $studyId): UserData;
	
	/**
	 * @throws CriticalException
	 */
	abstract public function getUserData(int $studyId): UserData;
	
	abstract public function close();
	abstract public function writeAndClose();
	abstract protected function createNewUserIdInteger(int $studyId): int;
	
}