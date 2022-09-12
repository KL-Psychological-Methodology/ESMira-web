<?php

namespace backend\fileSystem\subStores;

use backend\dataClasses\ErrorReportInfo;
use backend\fileSystem\loader\ErrorReportInfoLoader;
use backend\Main;
use backend\CriticalError;
use backend\fileSystem\PathsFS;
use backend\subStores\ErrorReportStore;

class ErrorReportStoreFS implements ErrorReportStore {
	public function hasErrorReports(): bool {
		$r = false;
		$path = PathsFS::folderErrorReports();
		$handle = opendir($path);
		while($file = readdir($handle)) {
			if($file[0] != '.') {
				$infoPath = PathsFS::fileErrorReportInfo($file);
				if(file_exists($infoPath)) {
					$info = ErrorReportInfoLoader::importFile((int) $file);
					if(!$info->seen) {
						$r = true;
						break;
					}
				}
				else {
					$r = true;
					break;
				}
			}
		}
		closedir($handle);
		return $r;
	}
	
	public function getList(): array {
		$list = [];
		$handle = opendir(PathsFS::folderErrorReports());
		while($file = readdir($handle)) {
			if($file[0] != '.') {
				$list[] = ErrorReportInfoLoader::importFile((int) $file);
			}
		}
		closedir($handle);
		return $list;
	}
	
	public function getErrorReport(int $timestamp): string {
		$path = PathsFS::fileErrorReport($timestamp);
		if(file_exists($path)) {
			return file_get_contents($path);
		}
		else
			throw new CriticalError('Not found');
	}
	public function saveErrorReport(string $msg): bool {
		$time = Main::getMilliseconds();
		
		$num = 0;
		do {
			$path = PathsFS::fileErrorReport($time + $num);
			if(++$num > 100)
				return false;
		} while(file_exists($path));
		
		return file_put_contents($path, $msg) && chmod($path, 0666);
	}
	
	/**
	 * @throws CriticalError
	 */
	public function changeErrorReport(ErrorReportInfo $errorReportInfo) {
		if(!file_exists(PathsFS::fileErrorReport($errorReportInfo->timestamp)))
			throw new CriticalError('Error report does not exist!');
		
		ErrorReportInfoLoader::exportFile($errorReportInfo);
	}
	
	public function removeErrorReport(int $timestamp) {
		$path = PathsFS::fileErrorReport($timestamp);
		
		if(!file_exists($path) || !unlink($path))
			throw new CriticalError("Could not remove $path");
	}
}