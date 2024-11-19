<?php

namespace backend\fileSystem\subStores;

use backend\Main;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\fileSystem\loader\PermissionsLoader;
use backend\Permission;
use backend\subStores\AccountStore;

class AccountStoreFS implements AccountStore
{
	public function getLoginHistoryCsv(string $accountName): string
	{
		$pathHistory1 = PathsFS::fileTokenHistory($accountName, 1);
		$pathHistory2 = PathsFS::fileTokenHistory($accountName, 2);
		$exists1 = file_exists($pathHistory1);
		$exists2 = file_exists($pathHistory2);

		$header = Main::arrayToCSV(['uploaded', 'login', 'ip', 'userAgent'], Configs::get('csv_delimiter'));

		if ($exists1 && $exists2) {
			if (filemtime($pathHistory1) < filemtime($pathHistory2))
				return $header . file_get_contents($pathHistory1) . file_get_contents($pathHistory2);
			else
				return $header . file_get_contents($pathHistory2) . file_get_contents($pathHistory1);
		} else if ($exists1)
			return $header . file_get_contents($pathHistory1);
		else if ($exists2)
			return $header . file_get_contents($pathHistory2);
		else
			return $header;
	}

	public function addToLoginHistoryEntry(string $accountName, array $data, int $maxAge = 60 * 60 * 24 * 60)
	{
		$pathToken = PathsFS::folderToken($accountName);
		if (!file_exists($pathToken))
			FileSystemBasics::createFolder($pathToken);

		$pathTokenHistory1 = PathsFS::fileTokenHistory($accountName, 1);
		$pathTokenHistory2 = PathsFS::fileTokenHistory($accountName, 2);

		if (!file_exists($pathTokenHistory1)) { //The very first entry is saved in $pathTokenHistory1
			$targetPath = $pathTokenHistory1;
			$flag = LOCK_EX;
		} else if (!file_exists($pathTokenHistory2)) { //The very second entry is saved in $pathTokenHistory2
			$targetPath = $pathTokenHistory2;
			$flag = LOCK_EX;
		} else { //Both files have been created:
			$now = time();
			$diff1 = $now - filemtime($pathTokenHistory1);
			$diff2 = $now - filemtime($pathTokenHistory2);

			if ($diff1 < $maxAge && $diff2 < $maxAge) { //as long as no history file gets to old, always add to the most recent one
				$targetPath = $diff1 < $diff2 ? $pathTokenHistory1 : $pathTokenHistory2;
				$flag = FILE_APPEND | LOCK_EX;
			} else { //until a history file gets to old. Then overwrite it (which will then become the most recent one)
				$targetPath = $diff1 > $diff2 ? $pathTokenHistory1 : $pathTokenHistory2; // overwrite the oldest one in case both are old
				$flag = LOCK_EX;
			}
		}

		file_put_contents($targetPath, "\n" . Main::arrayToCSV($data, Configs::get('csv_delimiter')), $flag);
	}


	public function getPermissions(string $accountName): array
	{
		$permissions = PermissionsLoader::importFile();
		return $permissions[$accountName] ?? [];
	}

	public function addStudyPermission(string $accountName, int $studyId, string $permCode)
	{
		$permissions = PermissionsLoader::importFile();

		if (!isset($permissions[$accountName]))
			$permissions[$accountName] = [$permCode => [$studyId]];
		else if (!isset($permissions[$accountName][$permCode]))
			$permissions[$accountName][$permCode] = [$studyId];
		else if (!in_array($studyId, $permissions[$accountName][$permCode]))
			$permissions[$accountName][$permCode][] = $studyId;

		PermissionsLoader::exportFile($permissions);
	}
	public function removeStudyPermission(string $accountName, int $studyId, string $permCode)
	{
		$permissions = PermissionsLoader::importFile();
		if (!isset($permissions[$accountName]) || !isset($permissions[$accountName][$permCode]))
			return;

		$value = array_search($studyId, $permissions[$accountName][$permCode]);
		if ($value !== false) {
			array_splice($permissions[$accountName][$permCode], $value, 1);
			if (empty($permissions[$accountName][$permCode])) {
				unset($permissions[$accountName][$permCode]);
				if (empty($permissions[$accountName])) {
					unset($permissions[$accountName]);
				}
			}
			PermissionsLoader::exportFile($permissions);
		}
	}
	public function setAdminPermission(string $accountName, bool $isAdmin)
	{
		$permissions = PermissionsLoader::importFile();

		if (!isset($permissions[$accountName]))
			$permissions[$accountName] = ['admin' => $isAdmin];
		else
			$permissions[$accountName]['admin'] = $isAdmin;

		PermissionsLoader::exportFile($permissions);
	}
	public function setCreatePermission(string $accountName, bool $canCreate)
	{
		$permissions = PermissionsLoader::importFile();

		if ($permissions[$accountName]['admin'])
			return;

		if (!isset($permissions[$accountName]))
			$permissions[$accountName] = ['create' => $canCreate];
		else
			$permissions[$accountName]['create'] = $canCreate;

		PermissionsLoader::exportFile($permissions);
	}


	private function movePermissions(string $oldAccountName, string $newAccountName)
	{
		$permissions = PermissionsLoader::importFile();
		if (!isset($permissions[$oldAccountName]) || isset($permissions[$newAccountName]))
			return;
		$permissions[$newAccountName] = $permissions[$oldAccountName];
		unset($permissions[$oldAccountName]);

		PermissionsLoader::exportFile($permissions);
	}

	public function removeBlocking(string $accountName)
	{
		$path = PathsFS::fileBlockLogin($accountName);
		if (file_exists($path))
			unlink($path);
	}
	public function createBlocking($accountName)
	{
		$pathToken = PathsFS::folderToken($accountName);
		if (!file_exists($pathToken))
			FileSystemBasics::createFolder($pathToken);

		$pathBlocking = PathsFS::fileBlockLogin($accountName);
		if (!file_exists($pathBlocking))
			file_put_contents($pathBlocking, 1);
		else {
			$num = (int)file_get_contents($pathBlocking);
			file_put_contents($pathBlocking, min($num * 2, Configs::get('max_blocked_seconds_for_login')));
		}
	}
	public function getAccountBlockedTime(string $accountName): int
	{
		$file_blocking = PathsFS::fileBlockLogin($accountName);
		$has_blockingFile = file_exists($file_blocking);
		if ($has_blockingFile) {
			$diff = (filemtime($file_blocking) + (int)file_get_contents($file_blocking)) - time();
			if ($diff > 0)
				return $diff;
		}
		return 0;
	}

	public function getAccountList(): array
	{
		$path = PathsFS::fileLogins();
		if (!file_exists($path))
			return [];
		if (!($h = fopen($path, 'r')))
			throw new CriticalException('Could not open logins file');

		$userList = [];

		while (!feof($h)) {
			$line = substr(fgets($h), 0, -1);
			if ($line == '')
				continue;
			$data = explode(':', $line);
			$accountName = $data[0];

			$userList[] = $accountName;
		}
		return $userList;
	}
	public function checkAccountLogin(string $accountName, string $password): bool
	{
		$path = PathsFS::fileLogins();
		if (!file_exists($path))
			return false;
		$h = fopen($path, 'r');
		if (!$h)
			return false;

		while (!feof($h)) {
			$line = substr(fgets($h), 0, -1);
			if ($line == '')
				continue;
			$data =  explode(':', $line);

			if ($data && $data[0] == $accountName) {
				fclose($h);
				return password_verify($password, $data[1]);
			}
		}
		fclose($h);
		return false;
	}
	public function doesAccountExist($accountName): bool
	{
		$path = PathsFS::fileLogins();
		if (!file_exists($path) || !($h = fopen($path, 'r')))
			return false;
		while (!feof($h)) {
			$line = fgets($h);
			if (!$line)
				continue;
			$data = explode(':', $line);

			if (!$data || empty($data))
				continue;

			if ($data[0] == $accountName) {
				fclose($h);
				return true;
			}
		}
		fclose($h);
		return false;
	}
	public function setAccount($accountName, $password)
	{
		$this->removeAccount($accountName);
		if (!$this->isAccountNameValid($accountName))
			throw new CriticalException("Invalid account name");
		$pathLogins = PathsFS::fileLogins();
		$password = Permission::getHashedPass($password);

		file_put_contents($pathLogins, "$accountName:$password\n", FILE_APPEND | LOCK_EX);
	}

	public function changeAccountName(string $oldAccountName, string $newAccountName)
	{
		if ($this->doesAccountExist($newAccountName))
			throw new CriticalException("$newAccountName already exists!");
		if (!$this->isAccountNameValid($newAccountName))
			throw new CriticalException("Invalid account name");

		$password = null;
		$pathLogins = PathsFS::fileLogins();
		if (!($h = fopen($pathLogins, 'r')))
			throw new CriticalException("Could not open $pathLogins");
		while (!feof($h)) {
			$line = fgets($h);
			$data = explode(':', $line);

			if (!empty($data) && $data[0] == $oldAccountName) {
				$password = $data[1];
				break;
			}
		}
		fclose($h);
		if ($password == null)
			throw new CriticalException("$oldAccountName does not exist!");

		$pathToken = PathsFS::folderToken($oldAccountName);
		if (file_exists($pathToken))
			rename($pathToken, PathsFS::folderToken($newAccountName)); //needs to be done before account is removed

		$this->removeAccount($oldAccountName);
		file_put_contents($pathLogins, "$newAccountName:$password\n", FILE_APPEND | LOCK_EX);

		$this->movePermissions($oldAccountName, $newAccountName);
	}

	public function removeAccount($accountName)
	{
		$pathToken = PathsFS::folderToken($accountName);
		if (file_exists($pathToken)) {
			FileSystemBasics::emptyFolder($pathToken);
			rmdir($pathToken);
		}

		$path = PathsFS::fileLogins();
		if (!file_exists($path))
			return;
		$export = '';
		if (!($handle = fopen($path, 'r')))
			throw new CriticalException("Could not open $path");
		$userRemoved = false;
		while (!feof($handle)) {
			$line = fgets($handle);
			if (!$line)
				continue;
			$data = explode(':', $line);

			if (empty($data))
				continue;

			if ($data[0] == $accountName)
				$userRemoved = true;
			else
				$export .= $line;
		}
		fclose($handle);

		if (!$userRemoved)
			return;

		if (empty($export))
			unlink($path);
		else
			FileSystemBasics::writeFile($path, $export);
	}

	private function isAccountNameValid($accountName): bool
	{
		$hasColon = strpos($accountName, ':') !== false;
		return !$hasColon;
	}
}
