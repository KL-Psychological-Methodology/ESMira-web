<?php

namespace backend\fileSystem\loader;

use backend\CriticalError;
use backend\dataClasses\ErrorReportInfo;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;

class ErrorReportInfoLoader {
	public static function importFile(): array {
		$path = PathsFS::fileErrorReportInfo();
		return file_exists($path) ? unserialize(file_get_contents($path)) : [];
	}
	
	/**
	 * @throws CriticalError
	 */
	public static function exportFile(array $errorReportInfo) {
		FileSystemBasics::writeFile(PathsFS::fileErrorReportInfo(), serialize($errorReportInfo));
	}
}