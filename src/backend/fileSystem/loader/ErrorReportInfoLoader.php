<?php

namespace backend\fileSystem\loader;

use backend\CriticalError;
use backend\dataClasses\ErrorReportInfo;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;

class ErrorReportInfoLoader {
	public static function importFile(int $timestamp): ErrorReportInfo {
		$path = PathsFS::fileErrorReportInfo($timestamp);
		return file_exists($path) ? unserialize(file_get_contents($path)) : new ErrorReportInfo($timestamp);
	}
	
	/**
	 * @throws CriticalError
	 */
	public static function exportFile(ErrorReportInfo $errorReportInfo) {
		FileSystemBasics::writeFile(PathsFS::fileErrorReportInfo($errorReportInfo->timestamp), serialize($errorReportInfo));
	}
}