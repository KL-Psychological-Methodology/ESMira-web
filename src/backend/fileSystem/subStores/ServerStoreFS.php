<?php

namespace backend\fileSystem\subStores;

use backend\CriticalError;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\subStores\ServerStore;

class ServerStoreFS implements ServerStore {
	
	public function getImpressum(string $langCode): string {
		$path = PathsFS::fileLangImpressum($langCode);
		return file_exists($path)
			? file_get_contents($path)
			: '';
	}
	public function saveImpressum(string $impressum, string $langCode) {
		$path = PathsFS::fileLangImpressum($langCode);
		FileSystemBasics::writeFile($path, $impressum);
	}
	public function deleteImpressum(string $langCode) {
		$path = PathsFS::fileLangImpressum($langCode);
		if(!file_exists($path))
			return;
		if(!unlink($path))
			throw new CriticalError("Could not delete $path");
	}
	
	public function getPrivacyPolicy(string $langCode): string {
		$path = PathsFS::fileLangPrivacyPolicy($langCode);
		return file_exists($path)
			? file_get_contents($path)
			: '';
	}
	
	public function savePrivacyPolicy(string $privacyPolicy, string $langCode) {
		$path = PathsFS::fileLangPrivacyPolicy($langCode);
		FileSystemBasics::writeFile($path, $privacyPolicy);
	}
	
	public function deletePrivacyPolicy(string $langCode) {
		$path = PathsFS::fileLangPrivacyPolicy($langCode);
		if(!file_exists($path))
			return;
		if(!unlink($path))
			throw new CriticalError("Could not delete $path");
	}
	
	public function getMediaFolderPath(int $studyId): string {
		return PathsFS::folderMedia($studyId);
	}
}