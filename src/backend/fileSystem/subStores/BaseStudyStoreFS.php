<?php

declare(strict_types=1);

namespace backend\fileSystem\subStores;

use backend\exceptions\CriticalException;
use backend\FileSystemBasics;
use backend\subStores\BaseStudyStore;
use backend\subStores\StudyAccessIndexStore;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use stdClass;

abstract class BaseStudyStoreFS implements BaseStudyStore
{
	abstract protected function getStudyPath(int $studyId): string;

	abstract protected function getLockfilePath(int $studyId): string;

	abstract protected function getStudiesFolderPath(): string;

	abstract protected function getStudyConfigPath(int $studyId): string;

	abstract protected function getLangConfigPath(int $studyId, $lang): string;

	abstract protected function getFolderLangsPath(int $studyId): string;

	abstract protected function getAccessKeyStore(): StudyAccessIndexStore;

	abstract protected function createFolders($studyId);

	public function studyExists(int $studyId): bool
	{
		return file_exists($this->getStudyPath($studyId));
	}

	public function isLocked(int $studyId): bool
	{
		return file_exists($this->getLockfilePath($studyId));
	}

	public function lockStudy(int $studyId, bool $lock = true)
	{
		$file = $this->getLockfilePath($studyId);

		if (file_exists($file)) {
			if (!$lock)
				unlink($file);
		} else {
			if ($lock)
				file_put_contents($file, '1');
		}
	}

	public function getStudyIdList(): array
	{
		$studies = [];
		$handle = opendir($this->getStudiesFolderPath());
		if ($handle === false)
			return [];
		while ($folderName = readdir($handle)) {
			$studyId = (int) $folderName;
			if ($studyId != 0) {
				$studies[] = $studyId;
			}
		}
		closedir($handle);
		return $studies;
	}

	private function getDirectorySize($path)
	{
		//thanks to https://stackoverflow.com/questions/478121/how-to-get-directory-size-in-php
		$bytestotal = 0;
		$path = realpath($path);
		if ($path !== false && $path != '' && file_exists($path)) {
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
				$bytestotal += $object->getSize();
			}
		}
		return $bytestotal;
	}

	public function getDirectorySizeOfStudies(): array
	{
		$directorySize = [];
		$folderStudies = $this->getStudiesFolderPath();
		if ($folderStudies === false)
			return [];
		$handle = opendir($folderStudies);
		while ($folderName = readdir($handle)) {
			$studyId = (int) $folderName;
			if ($studyId == 0)
				continue;

			$directorySize[$studyId] = $this->getDirectorySize("$folderStudies/$studyId");
		}
		closedir($handle);
		return $directorySize;
	}

	public function getStudyConfigAsJson(int $studyId): string
	{
		$path = $this->getStudyConfigPath($studyId);
		if (!file_exists($path))
			throw new CriticalException("Study id $studyId does not exist");
		return file_get_contents($path);
	}

	public function getStudyLangConfigAsJson(int $studyId, string $lang)
	{
		$path = $this->getLangConfigPath($studyId, $lang);
		return file_exists($path) ? file_get_contents($path) : $this->getStudyConfigAsJson($studyId);
	}

	public function getStudyConfig(int $studyId): stdClass
	{
		return json_decode($this->getStudyConfigAsJson($studyId));
	}

	public function getStudyLangConfig(int $studyId, string $lang): stdClass
	{
		return json_decode($this->getStudyLangConfigAsJson($studyId, $lang));
	}

	public function getAllLangConfigsAsJson(int $studyId): string
	{
		$path = $this->getFolderLangsPath($studyId);
		$langBox = [];
		if (file_exists($path)) {
			$h_folder = opendir($path);
			while ($file = readdir($h_folder)) {
				if ($file[0] != '.') {
					$s = file_get_contents($path . $file);
					$key = explode('.', $file)[0];
					$langBox[] = "\"$key\":$s";
				}
			}
			closedir($h_folder);
		}
		return '{' . implode(',', $langBox) . '}';
	}

	public function saveStudy(stdClass $studyCollection, array $questionnaireKeys)
	{
		$study = $studyCollection->_;
		$studyId = $study->id;

		$this->createFolders($studyId);

		$this->lockStudy($studyId);
		try {
			//
			//save study configs
			//
			FileSystemBasics::emptyFolder($this->getFolderLangsPath($studyId));
			$pathConfig = $this->getStudyConfigPath($studyId);
			foreach ($studyCollection as $code => $s) {
				$studyJson = json_encode($s);
				FileSystemBasics::writeFile($code === '_' ? $pathConfig : $this->getLangConfigPath($studyId, $code), $studyJson);
			}
		} finally {
			$this->lockStudy($studyId, false);
		}
	}

	public function delete(int $studyId)
	{
		if (!$this->studyExists($studyId))
			return;
		$folderStudy = $this->getStudyPath($studyId);
		if (file_exists($folderStudy)) {
			FileSystemBasics::emptyFolder($folderStudy);
			if (!rmdir($folderStudy))
				throw new CriticalException("Could not remove $folderStudy");
		} else
			throw new CriticalException("$folderStudy does not exist!");

		$accessKeyStore = $this->getAccessKeyStore();
		$accessKeyStore->removeStudy($studyId);
		$accessKeyStore->saveChanges();
	}
}
