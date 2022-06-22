<?php

namespace backend\fileSystem\subStores;

use backend\Main;
use backend\Configs;
use backend\CriticalError;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\fileSystem\loader\PermissionsLoader;
use backend\Permission;
use backend\subStores\UserStore;

class UserStoreFS implements UserStore {
	public function getLoginHistoryCsv(string $username): string {
		$pathHistory1 = PathsFS::fileTokenHistory($username, 1);
		$pathHistory2 = PathsFS::fileTokenHistory($username, 2);
		$exists1 = file_exists($pathHistory1);
		$exists2 = file_exists($pathHistory2);
		
		$header = Main::arrayToCSV(['login', 'ip', 'userAgent'], Configs::get('csv_delimiter'));
		
		if($exists1 && $exists2) {
			if(filemtime($pathHistory1) < filemtime($pathHistory2))
				return $header .file_get_contents($pathHistory1). file_get_contents($pathHistory2);
			else
				return $header .file_get_contents($pathHistory2). file_get_contents($pathHistory1);
		}
		else if($exists1)
			return $header .file_get_contents($pathHistory1);
		else if($exists2)
			return $header .file_get_contents($pathHistory2);
		else
			return $header;
	}
	
	public function addToLoginHistoryEntry(string $username, array $data, int $maxAge = 60 * 60 * 24 * 60) {
		$pathToken = PathsFS::folderToken($username);
		if(!file_exists($pathToken))
			FileSystemBasics::createFolder($pathToken);
		
		$pathTokenHistory1 = PathsFS::fileTokenHistory($username, 1);
		$pathTokenHistory2 = PathsFS::fileTokenHistory($username, 2);
		
		if(!file_exists($pathTokenHistory1)) { //The very first entry is saved in $pathTokenHistory1
			$targetPath = $pathTokenHistory1;
			$flag = LOCK_EX;
		}
		else if(!file_exists($pathTokenHistory2)) { //The very second entry is saved in $pathTokenHistory2
			$targetPath = $pathTokenHistory2;
			$flag = LOCK_EX;
		}
		else { //Both files have been created:
			$now = time();
			$diff1 = $now - filemtime($pathTokenHistory1);
			$diff2 = $now - filemtime($pathTokenHistory2);
			
			if($diff1 < $maxAge && $diff2 < $maxAge) { //as long as no history file gets to old, always add to the most recent one
				$targetPath = $diff1 < $diff2 ? $pathTokenHistory1 : $pathTokenHistory2;
				$flag = FILE_APPEND | LOCK_EX;
			}
			else { //until a history file gets to old. Then overwrite it (which will then become the most recent one)
				$targetPath = $diff1 > $diff2 ? $pathTokenHistory1 : $pathTokenHistory2; // overwrite the oldest one in case both are old
				$flag = LOCK_EX;
			}
		}
		
		file_put_contents($targetPath, "\n" .Main::arrayToCSV($data, Configs::get('csv_delimiter')), $flag);
	}
	
	
	public function getPermissions(string $username): array {
		$permissions = PermissionsLoader::importFile();
		return $permissions[$username] ?? [];
	}
	
	public function addStudyPermission(string $username, int $studyId, string $permCode) {
		$permissions = PermissionsLoader::importFile();
		
		if(!isset($permissions[$username]))
			$permissions[$username] = [$permCode => [$studyId]];
		else if(!isset($permissions[$username][$permCode]))
			$permissions[$username][$permCode] = [$studyId];
		else if(!in_array($studyId, $permissions[$username][$permCode]))
			$permissions[$username][$permCode][] = $studyId;
		
		PermissionsLoader::exportFile($permissions);
	}
	public function removeStudyPermission(string $username, int $studyId, string $permCode) {
		$permissions = PermissionsLoader::importFile();
		if(!isset($permissions[$username]) || !isset($permissions[$username][$permCode]))
			return;
		
		$value = array_search($studyId, $permissions[$username][$permCode]);
		if($value !== false) {
			array_splice($permissions[$username][$permCode], $value, 1);
			if(empty($permissions[$username][$permCode])) {
				unset($permissions[$username][$permCode]);
				if(empty($permissions[$username])) {
					unset($permissions[$username]);
				}
			}
			PermissionsLoader::exportFile($permissions);
		}
	}
	public function setAdminPermission(string $username, bool $isAdmin) {
		$permissions = PermissionsLoader::importFile();
		
		if(!isset($permissions[$username]))
			$permissions[$username] = ['admin' => $isAdmin];
		else
			$permissions[$username]['admin'] = $isAdmin;
		
		PermissionsLoader::exportFile($permissions);
	}
	
	public function removeBlocking(string $username) {
		$path = PathsFS::fileBlockLogin($username);
		if(file_exists($path))
			unlink($path);
	}
	public function createBlocking($username) {
		$pathToken = PathsFS::folderToken($username);
		if(!file_exists($pathToken))
			FileSystemBasics::createFolder($pathToken);
		
		$pathBlocking = PathsFS::fileBlockLogin($username);
		if(!file_exists($pathBlocking))
			file_put_contents($pathBlocking, 1);
		else {
			$num = (int)file_get_contents($pathBlocking);
			file_put_contents($pathBlocking, min($num * 2, Configs::get('max_blocked_seconds_for_login')));
		}
	}
	public function getUserBlockedTime(string $username): int {
		$file_blocking = PathsFS::fileBlockLogin($username);
		$has_blockingFile = file_exists($file_blocking);
		if($has_blockingFile) {
			$diff = (filemtime($file_blocking) + (int)file_get_contents($file_blocking)) - time();
			if($diff > 0)
				return $diff;
		}
		return 0;
	}
	
	public function getUserList(): array {
		$path = PathsFS::fileLogins();
		if(!file_exists($path))
			return [];
		if(!($h = fopen($path, 'r')))
			throw new CriticalError('Could not open logins file');
		
		$userList = [];
		
		while(!feof($h)) {
			$line = substr(fgets($h), 0, -1);
			if($line == '')
				continue;
			$data = explode(':', $line);
			$username = $data[0];
			
			$userList[] = $username;
		}
		return $userList;
	}
	public function checkUserLogin(string $username, string $password): bool {
		$path = PathsFS::fileLogins();
		if(!file_exists($path))
			return false;
		$h = fopen($path, 'r');
		if(!$h)
			return false;
		
		while(!feof($h)) {
			$line = substr(fgets($h), 0, -1);
			if($line == '')
				continue;
			$data =  explode(':', $line);
			
			if($data && $data[0] == $username) {
				fclose($h);
				return password_verify($password, $data[1]);
			}
		}
		fclose($h);
		return false;
	}
	public function doesUserExist($username): bool {
		$path = PathsFS::fileLogins();
		if(!file_exists($path) || !($h = fopen($path, 'r')))
			return false;
		while(!feof($h)) {
			$line = fgets($h);
			if(!$line)
				continue;
			$data = explode(':', $line);
			
			if(!$data || empty($data))
				continue;
			
			if($data[0] == $username) {
				fclose($h);
				return true;
			}
		}
		fclose($h);
		return false;
	}
	public function setUser($username, $password) {
		$this->removeUser($username);
		$pathLogins = PathsFS::fileLogins();
		$password = Permission::getHashedPass($password);
		
		file_put_contents($pathLogins, "$username:$password\n", FILE_APPEND | LOCK_EX);
	}
	
	public function changeUsername(string $oldUsername, string $newUsername) {
		if($this->doesUserExist($newUsername))
			throw new CriticalError("$newUsername already exists!");
		$password = null;
		$pathLogins = PathsFS::fileLogins();
		if(!($h = fopen($pathLogins, 'r')))
			throw new CriticalError("Could not open $pathLogins");
		while(!feof($h)) {
			$line = fgets($h);
			$data = explode(':', $line);
			
			if(!empty($data) && $data[0] == $oldUsername) {
				$password = $data[1];
				break;
			}
		}
		fclose($h);
		if($password == null)
			throw new CriticalError("$oldUsername does not exist!");
		
		$pathToken = PathsFS::folderToken($oldUsername);
		if(file_exists($pathToken))
			rename($pathToken, PathsFS::folderToken($newUsername)); //needs to be done before user is removed
		
		$this->removeUser($oldUsername);
		file_put_contents($pathLogins, "$newUsername:$password\n", FILE_APPEND | LOCK_EX);
	}
	
	public function removeUser($username) {
		$pathToken = PathsFS::folderToken($username);
		if(file_exists($pathToken)) {
			FileSystemBasics::emptyFolder($pathToken);
			rmdir($pathToken);
		}
		
		$path = PathsFS::fileLogins();
		if(!file_exists($path))
			return;
		$export = '';
		if(!($handle = fopen($path, 'r')))
			throw new CriticalError("Could not open $path");
		$userRemoved = false;
		while(!feof($handle)) {
			$line = fgets($handle);
			if(!$line)
				continue;
			$data = explode(':', $line);
			
			if(empty($data))
				continue;
			
			if($data[0] == $username)
				$userRemoved = true;
			else
				$export .= $line;
		}
		fclose($handle);
		
		if(!$userRemoved)
			return;
		
		if(empty($export))
			unlink($path);
		else
			FileSystemBasics::writeFile($path, $export);
	}
}