<?php

namespace backend\fileSystem\loader;

use backend\CriticalError;
use backend\FileSystemBasics;

trait MessagesLoader {
	/**
	 * @var resource
	 */
	private static $handle = null;
	
	protected abstract static function getPath(int $studyId, string $userId): string;
	
	/**
	 * @throws CriticalError
	 */
	public static function importFile(int $studyId, string $userId, bool $keepOpen = false): array {
		$path = static::getPath($studyId, $userId);
		
		if($keepOpen) {
			if(file_exists($path)) {
				static::$handle = fopen($path, 'r+');
				if(!static::$handle)
					throw new CriticalError("Could not open $path");
				flock(static::$handle, LOCK_EX);
				$content = fread(static::$handle, filesize($path));
			}
			else {
				static::$handle = fopen($path, 'w');
				if(!static::$handle)
					throw new CriticalError("Could not open $path");
				flock(static::$handle, LOCK_EX);
				return [];
			}
		}
		else {
			if(file_exists($path))
				$content = file_get_contents($path);
			else
				return [];
		}
		return empty($content) ? [] : unserialize($content);
	}
	
	/**
	 * @throws CriticalError
	 */
	public static function exportFile(int $studyId, string $userId, array $messages) {
		$path = static::getPath($studyId, $userId);
		
		if(!empty($messages)) {
			if(static::$handle) {
				fseek(static::$handle, 0);
				if(!ftruncate(static::$handle, 0)) {
					self::close();
					throw new CriticalError("Could not empty $path");
				}
				else if(!fwrite(static::$handle, serialize($messages))) {
					self::close();
					throw new CriticalError("Could not write to $path");
				}
			}
			else
				FileSystemBasics::writeFile($path, serialize($messages));
		}
		else if(file_exists($path) && !unlink($path)) {
			self::close();
			throw new CriticalError("Could not delete $path");
		}
		
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