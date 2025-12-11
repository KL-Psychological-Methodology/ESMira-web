<?php
declare(strict_types=1);

namespace backend;


use backend\exceptions\CriticalException;
use Iterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class FileSystemBasics {
	/**
	 * @throws CriticalException
	 */
	public static function writeFile(string $file, string $s) {
		if(!file_put_contents($file, $s, LOCK_EX))
			throw new CriticalException("Writing the file '$file' failed");
	}
	
	/**
	 * @throws CriticalException
	 */
	public static function createFolder(string $folder, bool $recursive = false) {
		if(file_exists($folder))
			return;
		if(!mkdir($folder, 0744, $recursive))
			throw new CriticalException("Creating the folder '$folder' failed");
	}
	
	/**
	 * @throws CriticalException
	 */
	public static function emptyFolder(string $path): bool {
		$handle = opendir($path);
		if(!$handle)
			throw new CriticalException("Could not open '$path'");
		while($file = readdir($handle)) {
			if($file == '.' || $file == '..')
				continue;
			
			$filename = "$path/$file";
			if(is_dir($filename)) {
				if(!self::emptyFolder($filename . '/') || !rmdir($filename)) {
					closedir($handle);
					return false;
				}
			}
			else {
				if(!unlink($filename)) {
					closedir($handle);
					return false;
				}
			}
		}
		closedir($handle);
		return true;
	}
	
	/**
	 * Move all files from $oldParent to $newParent. $oldParent will not be removed and $newParent is expected to already exist.
	 * Note when using a custom $iterator: The iterator MUST filter "." and ".." or moveOneByOne() will most like throw an exception.
	 *
	 * @param string $oldParent Path to a directory. Its content will be moved to $newParent
	 * @param string $newParent Path to a directory. The target where the contents from $oldParent should be moved to.
	 * @param bool $replaceExisting If false, throws an exception if a file that is about to be moved already exists in $newParent. Deletes it otherwise.
	 * @param callable|null $reportProgress A callback function to report the progress to. Its arguments are: int $fileNum, int $totalfiles
	 * @param Iterator|null $iterator A custom iterator for files. Beware that the iterator MUST filter "." and ".."
	 * @throws CriticalException
	 */
	public static function moveOneByOne(string $oldParent, string $newParent, bool $replaceExisting = false, ?callable $reportProgress = null, ?Iterator $iterator = null) {
		$removeOuterDirectory = function (string $relativePath) use($oldParent) {
			while($relativePath != '' && $relativePath != '.' && $relativePath != '/') {
				if(FileSystemBasics::isDirEmpty($oldParent . $relativePath)) {
					rmdir($oldParent . $relativePath);
				}
				else {
					break;
				}
				$relativePath = dirname($relativePath);
			}
		};
		
		$oldParent = rtrim($oldParent, '/'); // substr() would fail if $oldParent ends with a slash
		$newParent = rtrim($newParent, '/');
		
		if(!$iterator) {
			$directory = new RecursiveDirectoryIterator($oldParent);
			$filter = new RecursiveCallbackFilterIterator($directory, function(SplFileInfo $current) {
				return $current->getFilename() != '.' && $current->getFilename() != '..';
			});
			$iterator = new RecursiveIteratorIterator($filter);
		}
		
		$totalFiles = $reportProgress ? iterator_count($iterator) : 0;
		$fileNum = 0;
		
		foreach($iterator as $file) {
			$oldChild = $file->getRealPath();
			
			$relativePath = substr($oldChild, strlen($oldParent)); // if substr() is false, than you might have forgotten ti filter "." in the iterator
			$newChild = $newParent . $relativePath;
			if($file->isDir()) {
				FileSystemBasics::createFolder($newChild, true);
			}
			else {
				if(file_exists($newChild)) {
					if($replaceExisting) {
						if(!unlink($newChild)) {
							throw new CriticalException("Could not delete file $newChild");
						}
					}
					else {
						throw new CriticalException("$newChild already exists! Cannot move $oldChild");
					}
				}
				
				$relativePath = dirname($relativePath);
				$newFolder = $newParent . $relativePath;
				if(!file_exists($newFolder)) {
					FileSystemBasics::createFolder($newFolder, true);
				}
				if(!rename($oldChild, $newChild)) {
					throw new CriticalException("Renaming $oldChild to $newChild failed");
				}
			}
			
			$removeOuterDirectory($relativePath);
			
			if($reportProgress) {
				$reportProgress(++$fileNum, $totalFiles);
			}
		}
	}
	
	public static function isDirEmpty($path): bool {
		//Thanks to:
		//https://stackoverflow.com/questions/7497733/how-can-i-use-php-to-check-if-a-directory-is-empty
		return (count(scandir($path)) == 2);
	}
	
	/**
	 * @throws CriticalException
	 */
	public static function writeServerConfigs(array $newValues) { //$pathConfig is used for testing
		$saveValues = array_merge(Configs::getDefaultAll(), Configs::getAll(), $newValues);
		FileSystemBasics::createFolder(dirname(Paths::FILE_CONFIG));
		self::writeFile(Paths::FILE_CONFIG, '<?php return ' . var_export($saveValues, true) . ';');
		Configs::resetConfig($saveValues);
	}
	
	public static function deleteServerConfigs() {
		unlink(Paths::FILE_CONFIG);
	}
}