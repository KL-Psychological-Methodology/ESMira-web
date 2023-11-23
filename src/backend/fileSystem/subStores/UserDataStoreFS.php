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
use backend\Paths;
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
		
		if(file_exists($pathUserData)) {
			$handle = fopen($pathUserData, 'r+');
			
			if($handle) {
				flock($handle, LOCK_EX);
				$this->fileHandles[$studyId] = $handle;
				$filesize = filesize($pathUserData);
				
				try {
					if($filesize == 0) {
						Main::report(
							"The userData file seems to be empty (happened in getUserDataForWriting)!\n"
							."The file $pathUserData will be recreated (the userData file does not contain vital information)\n"
							."Study: $studyId\n"
							."User-Id: $this->userId"
						);
						$userData = $this->createNewUserData($studyId);
						$userData->joinedTime = filectime($pathUserData); //on unix, this will be the last-changed time. Which is less than ideal
					}
					else
						$userData = UserDataLoader::import(fread($handle, $filesize));
				}
				catch(CriticalException $e) {
					Main::reportError($e,
						"The userData file seems to be corrupt (happened in getUserDataForWriting)!\n"
						."The file $pathUserData will be recreated (the userData file does not contain vital information)\n"
						."Study: $studyId\n"
						."User-Id: $this->userId"
					);
					$userData = $this->createNewUserData($studyId);
					$userData->joinedTime = filectime($pathUserData); //on unix, this will be the last-changed time. Which is less than ideal
				}
				
			}
			else {
				Main::report("Could not open token for user \"$this->userId\" in study $studyId");
				throw new CriticalException('Internal token error');
			}
		}
		else {
			$handle = fopen($pathUserData, 'x');
			
			if($handle) {
				flock($handle, LOCK_EX);
				$this->fileHandles[$studyId] = $handle;
			}
			else
				Main::report("Could not create token for user \"$this->userId\" in study $studyId");
			
			$userData = $this->createNewUserData($studyId);
			$userData->token = -1;
			$this->isNewUser[$studyId] = true;
		}
		
		
		$this->userDataArray[$studyId] = $userData;
		return $userData;
	}
	
	public function getUserData(int $studyId): UserData {
		$pathUserData = PathsFS::fileUserData($studyId, $this->userId);
		if(!file_exists($pathUserData))
			throw new CriticalException("No user data for study $studyId");
		try {
			return UserDataLoader::import(file_get_contents($pathUserData));
		}
		catch(CriticalException $e) {
			Main::reportError($e,
				"The userData file seems to be corrupt (happened in getUserData)!\n"
				."The file $pathUserData will be recreated (the userData file does not contain vital information)\n"
				."Study: $studyId\n"
				."User-Id: $this->userId"
			);
			$userData = $this->createNewUserData($studyId);
			$userData->joinedTime = filectime($pathUserData); //on unix this will be the last-changed time. Which is less than ideal
			UserDataLoader::export($userData);
			return $userData;
		}
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