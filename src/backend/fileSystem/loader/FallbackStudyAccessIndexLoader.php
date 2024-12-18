<?php

namespace backend\fileSystem\loader;

use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;

class FallbackStudyAccessIndexLoader
{
	public static function importFile(string $encodedUrl): array
	{
		$path = PathsFS::fileFallbackStudyIndex($encodedUrl);
		return file_exists($path) ? unserialize(file_get_contents($path)) : [];
	}

	public static function exportFile(string $encodedUrl, array $studyIndex)
	{
		FileSystemBasics::writeFile(PathsFS::fileFallbackStudyIndex($encodedUrl), serialize($studyIndex));
	}
}