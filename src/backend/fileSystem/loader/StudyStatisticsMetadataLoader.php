<?php

namespace backend\fileSystem\loader;

use backend\exceptions\CriticalException;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use stdClass;

class StudyStatisticsMetadataLoader {
	//TODO: use faster data format
	static function importFile($studyId): array {
		$pathStatisticsMetadata = PathsFS::fileStudyStatisticsMetadata($studyId);
		return file_exists($pathStatisticsMetadata)
			? unserialize(file_get_contents($pathStatisticsMetadata))
			: [];
	}
	
	/**
	 * @throws CriticalException
	 */
	static function exportFile(int $studyId, array $metadata) {
		$pathMetadata = PathsFS::fileStudyStatisticsMetadata($studyId);
		FileSystemBasics::writeFile($pathMetadata, serialize($metadata));
	}
}