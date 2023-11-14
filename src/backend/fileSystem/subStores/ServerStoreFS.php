<?php

namespace backend\fileSystem\subStores;

use backend\exceptions\CriticalException;
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
			throw new CriticalException("Could not delete $path");
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
			throw new CriticalException("Could not delete $path");
	}
	
	public function getMediaFolderPath(int $studyId): string {
		return PathsFS::folderMedia($studyId);
	}
	
	public function getHomeMessage(string $langCode): string {
		$path = PathsFS::fileLangHomeMessage($langCode);
		return file_exists($path)
			? file_get_contents($path)
			: '';
	}
	public function saveHomeMessage(string $homeMessage, string $langCode) {
		$path = PathsFS::fileLangHomeMessage($langCode);
		FileSystemBasics::writeFile($path, $homeMessage);
	}
	
	public function deleteHomeMessage(string $langCode) {
		$path = PathsFS::fileLangHomeMessage($langCode);
		if(!file_exists($path))
			return;
		if(!unlink($path))
			throw new CriticalException("Could not delete $path");
	}
}