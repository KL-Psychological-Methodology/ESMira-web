<?php

namespace backend\fileSystem\loader;

use backend\CriticalError;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;

class StudyAccessKeyIndexLoader {
	public static function importFile(): array {
		$path = PathsFS::fileStudyIndex();
		return file_exists($path) ? unserialize(file_get_contents($path)) : [];
	}
	
	/**
	 * @throws CriticalError
	 */
	public static function exportFile(array $studyIndex) {
		FileSystemBasics::writeFile(PathsFS::fileStudyIndex(), serialize($studyIndex));
	}
}