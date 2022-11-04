<?php
declare(strict_types=1);

namespace backend\fileSystem\subStores;

use backend\dataClasses\RewardCodeData;
use backend\fileSystem\loader\RewardCodeDataLoader;
use backend\FileSystemBasics;
use backend\Main;
use backend\exceptions\CriticalException;
use backend\dataClasses\UserData;
use backend\fileSystem\PathsFS;
use backend\fileSystem\loader\UserDataLoader;
use backend\subStores\UserDataStore;

class UserDataStoreFS extends UserDataStore {
	private $fileHandles = [];
	
	protected function createNewUserIdInteger(int $studyId): int {
		$count = 0;
		$handle = opendir(PathsFS::folderUserData($studyId));
		while($folder = readdir($handle)) {
			if($folder[0] != '.') {
				++$count;
			}
		}
		closedir($handle);
		return $count;
	}
	
	protected function getUserDataForWriting(int $studyId): UserData {
		if(isset($this->fileHandles[$studyId]))
			throw new CriticalException("Userdata $this->userId for study $studyId is already opened");
		$pathUserData = PathsFS::fileUserData($studyId, $this->userId);
		
		if(file_exists($pathUserData) && ($fileSize = filesize($pathUserData)) != 0) {
			$handle = fopen($pathUserData, 'r+');
			
			if($handle) {
				$this->fileHandles[$studyId] = $handle;
				$userData = UserDataLoader::import(fread($handle, $fileSize));
			}
			else {
				Main::report("Could not open token for user \"$this->userId\" in study $studyId");
				throw new CriticalException('Internal token error');
			}
		}
		else {
			$handle = fopen($pathUserData, 'w');
			
			if($handle)
				$this->fileHandles[$studyId] = $handle;
			else
				Main::report("Could not create token for user \"$this->userId\" in study $studyId");
			
			$userData = $this->createNewUserData($studyId);
			$userData->token = -1;
			$this->isNewUser[$studyId] = true;
		}
		
		
		flock($handle, LOCK_EX);
		$this->userDataArray[$studyId] = $userData;
		return $userData;
	}
	
	public function getUserData(int $studyId): UserData {
		$pathUserData = PathsFS::fileUserData($studyId, $this->userId);
		if(!file_exists($pathUserData))
			throw new CriticalException("No user data for study $studyId");
		return UserDataLoader::import(file_get_contents($pathUserData));
	}
	
	public function close() {
		foreach($this->fileHandles as $handle) {
			flock($handle, LOCK_UN);
			fclose($handle);
		}
		$this->userDataArray = [];
		$this->fileHandles = [];
	}
	public function writeAndClose() {
		foreach($this->fileHandles as $studyId => $handle) {
			$userdata = $this->userDataArray[$studyId];
			$userdata->token = $this->newStudyToken;
			
			fseek($handle, 0);
			ftruncate($handle, 0);
			
			if(!fwrite($handle, UserDataLoader::export($userdata)))
				Main::report("Could not save token for user \"$this->userId\" in study $studyId");
			
			
			fflush($handle);
			flock($handle, LOCK_UN);
			fclose($handle);
		}
		$this->userDataArray = [];
		$this->fileHandles = [];
	}
}