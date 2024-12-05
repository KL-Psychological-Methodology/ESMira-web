<?php

use backend\fileSystem\loader\FallbackStudyAccessIndexLoader;
use backend\fileSystem\subStores\StudyAccessIndexStoreFS;
use backend\subStores\FallbackStudyAccessIndexStore;

class FallbackStudyAccessIndexStoreFS extends StudyAccessIndexStoreFS implements FallbackStudyAccessIndexStore
{
	private $encodedUrl;
	public function __construct(string $encodedUrl)
	{
		$this->encodedUrl = $encodedUrl;
		$this->studyIndex = FallbackStudyAccessIndexLoader::importFile($encodedUrl);
	}

	public function saveChanges()
	{
		if (!$this->wasChanged)
			return;

		FallbackStudyAccessIndexLoader::exportFile($this->encodedUrl, $this->studyIndex);
	}
}