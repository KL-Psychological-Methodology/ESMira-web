<?php

namespace backend\fileSystem\loader;

use backend\fileSystem\PathsFS;
use backend\Main;
use backend\exceptions\CriticalException;
use backend\FileSystemBasics;
use backend\ResponsesIndex;

class ResponsesIndexLoader {
	/**
	 * @throws CriticalException
	 */
	public static function importFile(int $studyId, string $identifier): ResponsesIndex {
		$pathIndex = PathsFS::fileResponsesIndex($studyId, $identifier);
		if(!file_exists($pathIndex)) {
			Main::report("$pathIndex did not exist when calling QuestionnaireIndexLoader::import()!");
			throw new CriticalException("Study seems to be broken");
		}
		
		return unserialize(file_get_contents($pathIndex));
	}
	
	/**
	 * @throws CriticalException
	 */
	public static function exportFile(int $studyId, string $identifier, ResponsesIndex $responsesIndex) {
		FileSystemBasics::writeFile(PathsFS::fileResponsesIndex($studyId, $identifier), serialize($responsesIndex));
	}
}