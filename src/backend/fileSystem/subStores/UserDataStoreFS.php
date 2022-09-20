<?php
declare(strict_types=1);

namespace backend\fileSystem\subStores;

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
	
	protected function loadUserDataIntoClass(int $studyId, int $group, string $appType, string $appVersion) {
		if(isset($this->fileHandles[$studyId]))
			return;
		$pathUserData = PathsFS::fileUserData($studyId, $this->userId);
		
		if(file_exists($pathUserData) && ($fileSize = filesize($pathUserData)) != 0) {
			$handle = fopen($pathUserData, 'r+');
			
			if($handle) {
				$this->fileHandles[$studyId] = $handle;
				$userdata = UserDataLoader::import(fread($handle, $fileSize));
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
			
			$userdata = $this->createNewUserData($studyId, $group, $appType, $appVersion);
			$userdata->token = -1;
			$this->isNewUser[$studyId] = true;
		}
		
		$this->userDataArray[$studyId] = $userdata;
		flock($handle, LOCK_EX);
	}
	
	public function getUserData(int $studyId): UserData {
		$pathUserData = PathsFS::fileUserData($studyId, $this->userId);
		return UserDataLoader::import(file_get_contents($pathUserData));
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
		$this->fileHandles = [];
	}
}