<?php

namespace backend\fileSystem\subStores;

use backend\dataClasses\ErrorReportInfo;
use backend\fileSystem\loader\ErrorReportInfoLoader;
use backend\Main;
use backend\exceptions\CriticalException;
use backend\fileSystem\PathsFS;
use backend\subStores\ErrorReportStore;

class ErrorReportStoreFS implements ErrorReportStore {
	public function hasErrorReports(): bool {
		$r = false;
		$path = PathsFS::folderErrorReports();
		$errorInfo = ErrorReportInfoLoader::importFile();
		$handle = opendir($path);
		while($file = readdir($handle)) {
			if($file[0] != '.') {
				if(isset($errorInfo[(int) $file])) {
					if(!$errorInfo[(int) $file]->seen) {
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
		$errorInfo = ErrorReportInfoLoader::importFile();
		$handle = opendir(PathsFS::folderErrorReports());
		while($file = readdir($handle)) {
			if($file[0] != '.') {
				$list[] = $errorInfo[(int) $file] ?? new ErrorReportInfo((int) $file);
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
			throw new CriticalException('Not found');
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
	 * @throws CriticalException
	 */
	public function changeErrorReport(ErrorReportInfo $errorReportInfo) {
		if(!file_exists(PathsFS::fileErrorReport($errorReportInfo->timestamp)))
			throw new CriticalException('Error report does not exist!');
		
		$errorInfo = ErrorReportInfoLoader::importFile();
		$errorInfo[$errorReportInfo->timestamp] = $errorReportInfo;
		ErrorReportInfoLoader::exportFile($errorInfo);
	}
	
	public function removeErrorReport(int $timestamp) {
		$path = PathsFS::fileErrorReport($timestamp);
		
		if(!file_exists($path) || !unlink($path))
			throw new CriticalException("Could not remove $path");
		
		$errorInfo = ErrorReportInfoLoader::importFile();
		if(isset($errorInfo[$timestamp]))
			unset($errorInfo[$timestamp]);
		ErrorReportInfoLoader::exportFile($errorInfo);
	}
}