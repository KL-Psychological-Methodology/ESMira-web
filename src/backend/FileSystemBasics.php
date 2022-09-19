<?php
declare(strict_types=1);

namespace backend;


class FileSystemBasics {
	/**
	 * @throws CriticalError
	 */
	public static function writeFile(string $file, string $s) {
		if(!file_put_contents($file, $s, LOCK_EX))
			throw new CriticalError("Writing the file '$file' failed");
		else
			chmod($file, 0666);
	}
	
	/**
	 * @throws CriticalError
	 */
	public static function createFolder(string $folder) {
		if(file_exists($folder))
			return;
		if(!mkdir($folder, 0775) || !chmod($folder, 0775))
			throw new CriticalError("Creating the folder '$folder' failed");
	}
	
	/**
	 * @throws CriticalError
	 */
	public static function emptyFolder(string $path): bool {
		$handle = opendir($path);
		if(!$handle)
			throw new CriticalError("Could not open '$path'");
		while($file = readdir($handle)) {
			if($file == '.' || $file == '..')
				continue;
			
			$filename = "$path/$file";
			if(is_dir($filename)) {
				if(!self::emptyFolder($filename . '/') || !@rmdir($filename)) {
					closedir($handle);
					return false;
				}
			}
			else {
				if(!@unlink($filename)) {
					closedir($handle);
					return false;
				}
			}
		}
		closedir($handle);
		return true;
	}
	
	/**
	 * @throws CriticalError
	 */
	public static function writeServerConfigs(array $newValues) { //$pathConfig is used for testing
		$saveValues = array_merge(Configs::getDefaultAll(), Configs::getAll(), $newValues);
		self::writeFile(Paths::FILE_CONFIG, '<?php return ' . var_export($saveValues, true) . ';');
		Configs::resetConfig();
	}
}