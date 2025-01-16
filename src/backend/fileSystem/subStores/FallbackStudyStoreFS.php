<?php

namespace backend\fileSystem\subStores;

use backend\Configs;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\subStores\FallbackStudyStore;
use backend\subStores\StudyAccessIndexStore;

class FallbackStudyStoreFS extends BaseStudyStoreFS implements FallbackStudyStore
{
	private $encodedUrl;

	public function __construct(string $encodedUrl)
	{
		$this->encodedUrl = $encodedUrl;
	}

	protected function getStudyPath(int $studyId): string
	{
		return PathsFS::folderFallbackStudy($this->encodedUrl, $studyId);
	}

	protected function getLockfilePath(int $studyId): string
	{
		return PathsFS::fileFallbackStudyLock($this->encodedUrl, $studyId);
	}

	protected function getStudiesFolderPath(): string
	{
		return PathsFS::folderFallbackStudiesUrl($this->encodedUrl);
	}

	protected function getStudyConfigPath(int $studyId): string
	{
		return PathsFS::fileFallbackStudyConfig($this->encodedUrl, $studyId);
	}

	protected function getLangConfigPath(int $studyId, $lang): string
	{
		return PathsFS::fileFallbackStudyConfig($this->encodedUrl, $studyId);
	}

	protected function getFolderLangsPath(int $studyId): string
	{
		return PathsFS::folderFallbackStudyLangs($this->encodedUrl, $studyId);
	}

	protected function getAccessKeyStore(): StudyAccessIndexStore
	{
		return Configs::getDataStore()->getFallbackStudyAccessIndexStore($this->encodedUrl);
	}

	protected function createFolders($studyId)
	{
		//
		//create folders
		//
		FileSystemBasics::createFolder(PathsFS::folderFallbackStudies());
		FileSystemBasics::createFolder(PathsFS::folderFallbackStudiesUrl($this->encodedUrl));
		FileSystemBasics::createFolder(PathsFS::folderFallbackStudy($this->encodedUrl, $studyId));
		FileSystemBasics::createFolder(PathsFS::folderFallbackStudyLangs($this->encodedUrl, $studyId));
	}

	public function deleteStore()
	{
		$fallbackStudyStorePath = PathsFS::folderFallbackStudiesUrl($this->encodedUrl);
		if (file_exists($fallbackStudyStorePath)) {
			FileSystemBasics::emptyFolder($fallbackStudyStorePath);
			@rmdir($fallbackStudyStorePath);
		}
	}
}