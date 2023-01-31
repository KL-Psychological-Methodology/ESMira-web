<?php

namespace backend\fileSystem\loader;

use backend\exceptions\CriticalException;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use stdClass;

class StudyStatisticsLoader {
	/**
	 * @var resource
	 */
	private static $handle = null;
	
	/**
	 * @throws CriticalException
	 */
	static function importFile(int $studyId, bool $keepOpen = false): stdClass {
		$pathJson = PathsFS::fileStatisticsJson($studyId);
		
		if($keepOpen) {
			if(file_exists($pathJson)) {
				static::$handle = fopen($pathJson, 'r+');
				if(!static::$handle)
					throw new CriticalException("Could not open $pathJson");
				flock(static::$handle, LOCK_EX);
				$content = fread(static::$handle, filesize($pathJson));
			}
			else {
				static::$handle = fopen($pathJson, 'x');
				if(!static::$handle)
					throw new CriticalException("Could not open $pathJson");
				flock(static::$handle, LOCK_EX);
				return new stdClass();
			}
		}
		else {
			if(file_exists($pathJson))
				$content = file_get_contents($pathJson);
			else
				return new stdClass();
		}
		return empty($content) ? new stdClass() : json_decode($content);
	}
	
	/**
	 * @throws CriticalException
	 */
	static function exportFile(int $studyId, stdClass $json) {
		$pathJson = PathsFS::fileStatisticsJson($studyId);
		
		if(static::$handle) {
			fseek(static::$handle, 0);
			if(!ftruncate(static::$handle, 0)) {
				self::close();
				throw new CriticalException("Could not empty $pathJson");
			}
			else if(!fwrite(static::$handle, json_encode($json))) {
				self::close();
				throw new CriticalException("Could not write to $pathJson");
			}
		}
		else
			FileSystemBasics::writeFile($pathJson, json_encode($json));
		
		self::close();
	}
	
	public static function close() {
		if(static::$handle) {
			fflush(static::$handle);
			flock(static::$handle, LOCK_UN);
			fclose(static::$handle);
			static::$handle = null;
		}
	}
}