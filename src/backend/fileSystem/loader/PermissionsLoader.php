<?php

namespace backend\fileSystem\loader;

use backend\exceptions\CriticalException;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;

class PermissionsLoader {
	/**
	 * @var array
	 */
	private static $cache = null;
	public static function importFile(): array {
		if(self::$cache)
			return self::$cache;
		$path = PathsFS::filePermissions();
		return self::$cache = file_exists($path) ? unserialize(file_get_contents($path)) : [];
	}
	
	/**
	 * @throws CriticalException
	 */
	public static function exportFile(array $permissions) {
		$path = PathsFS::filePermissions();
		FileSystemBasics::writeFile($path, serialize($permissions));
		self::$cache = $permissions;
	}
	
	public static function reset() { //for testing
		self::$cache = null;
	}
}