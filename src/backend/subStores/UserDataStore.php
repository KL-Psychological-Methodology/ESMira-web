<?php
declare(strict_types=1);

namespace backend\subStores;

use backend\Main;
use backend\Configs;
use backend\CriticalError;
use backend\dataClasses\UserData;
use stdClass;

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
	
	protected function createNewUserData(int $studyId, int $group, string $appType, string $appVersion): UserData {
		return new UserData(
			$this->createNewUserIdInteger($studyId),
			$this->newStudyToken,
			0,
			$group,
			$appType,
			$appVersion
		);
	}
	
	/**
	 * @throws CriticalError
	 */
	public function addDataSetForSaving(int $studyId, int $group, string $appType, string $appVersion): bool {
		$this->loadUserDataIntoClass($studyId, $group, $appType, $appVersion);
		$userData = $this->userDataArray[$studyId];
		++$userData->dataSetCount;
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
	
	abstract public function getUserData(int $studyId): UserData;
	
	abstract public function writeAndClose();
	abstract protected function createNewUserIdInteger(int $studyId): int;
	
	/**
	 * @throws CriticalError
	 */
	abstract protected function loadUserDataIntoClass(int $studyId, int $group, string $appType, string $appVersion);
}