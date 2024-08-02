<?php

namespace backend\fileSystem\subStores;

use backend\dataClasses\MerlinLogInfo;
use backend\exceptions\CriticalException;
use backend\fileSystem\loader\MerlinLogInfoLoader;
use backend\fileSystem\PathsFS;
use backend\Main;
use backend\Permission;
use backend\subStores\MerlinLogsStore;

class MerlinLogsStoreFS implements MerlinLogsStore {
	private function hasUnreadLogs(int $studyId): bool {
		$r = false;
		$path = PathsFS::folderMerlinLogs($studyId);
		$errorInfo = MerlinLogInfoLoader::importFile($studyId);
		$handle = opendir($path);
		while($file = readdir($handle)) {
			if($file[0] != '.') {
				if(isset($errorInfo[(int)$file])) {
					if(!$errorInfo[(int)$file]->seen) {
						$r = true;
						break;
					}
				} else {
					$r = true;
					break;
				}
			}
		}
		closedir($handle);
		return $r;
	}
	
	public function getStudiesWithUnreadMerlinLogsForPermission(): array {
		$permissions = Permission::getPermissions();
		$isAdmin = $permissions['admin'] ?? false;
		$readPermissions = $permissions['read'] ?? [];
		$ids = [];
		$handle = opendir(PathsFS::folderStudies());
		while($studyId = readdir($handle)) {
			if($studyId[0] === '.' || $studyId === PathsFS::FILENAME_STUDY_INDEX)
				continue;
			if(($isAdmin || in_array($studyId, $readPermissions)) && $this->hasUnreadLogs((int)$studyId)) {
				$ids[] = $studyId;
			}
		}
		closedir($handle);
		return $ids;
	}
	
	public function getMerlinLogsList(int $studyId): array {
		$list = [];
		$logInfo = MerlinLogInfoLoader::importFile($studyId);
		$handle = opendir(PathsFS::folderMerlinLogs($studyId));
		while($file = readdir($handle)) {
			if($file[0] != '.') {
				$list[] = $logInfo[(int)$file] ?? new MerlinLogInfo((int)$file);
			}
		}
		closedir($handle);
		return $list;
	}
	
	public function getMerlinLog(int $studyId, int $timestamp): string {
		$path = PathsFS::fileMerlinLog($studyId, $timestamp);
		if(file_exists($path)) {
			return file_get_contents($path);
		} else {
			throw new CriticalException('Not found');
		}
	}
	
	public function receiveMerlinLog(int $studyId, string $msg): bool {
		$time = Main::getMilliseconds();
		
		$num = 0;
		do {
			$path = PathsFS::fileMerlinLog($studyId, $time + $num);
			if(++$num > 100)
				return false;
		} while(file_exists($path));
		
		return file_put_contents($path, $msg) && chmod($path, 0666);
	}
	
	public function changeMerlinLog(int $studyId, MerlinLogInfo $merlinLogInfo) {
		if(!file_exists(PathsFS::fileMerlinLog($studyId, $merlinLogInfo->timestamp)))
			throw new CriticalException('Log does not exist!');
		
		$logInfo = MerlinLogInfoLoader::importFile($studyId);
		$logInfo[$merlinLogInfo->timestamp] = $merlinLogInfo;
		MerlinLogInfoLoader::exportFile($studyId, $logInfo);
	}
	
	public function removeMerlinLog(int $studyId, int $timestamp) {
		$path = PathsFS::fileMerlinLog($studyId, $timestamp);
		
		if(!file_exists($path) || !unlink($path))
			throw new CriticalException("Could not remove $path");
		
		$logInfo = MerlinLogInfoLoader::importFile($studyId);
		if(isset($logInfo[$timestamp]))
			unset($logInfo[$timestamp]);
		MerlinLogInfoLoader::exportFile($studyId, $logInfo);
	}
}