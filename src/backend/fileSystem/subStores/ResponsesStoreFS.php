<?php

namespace backend\fileSystem\subStores;

use backend\Main;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\DataSetCache;
use backend\DataSetCacheContainer;
use backend\Paths;
use backend\fileSystem\PathsFS;
use backend\fileSystem\loader\StatisticsNewDataSetEntryLoader;
use backend\FileUploader;
use backend\subStores\ResponsesStore;
use ZipArchive;

class ResponsesStoreFS implements ResponsesStore {
	private function fillDatasetErrors(DataSetCacheContainer $entry, $msg, callable $errorCallback) {
		foreach($entry->ids as $datasetId) {
			$errorCallback($datasetId, $msg);
		}
	}
	private function writeDataSetFile(string $path, string $text, DataSetCacheContainer $entry, callable $successCallback, callable $errorCallback) {
		if(file_put_contents($path, $text, FILE_APPEND | LOCK_EX)) {
			foreach($entry->ids as $datasetId) {
				$successCallback($datasetId);
			}
		}
		else {
			Main::report("Could not write to file '$path'. Sending error response to app.");
			$this->fillDatasetErrors($entry, 'Internal Server Error: Saving failed', $errorCallback);
		}
	}
	private function writeCsv(
		string $path,
		string $noPathMessage,
		DataSetCacheContainer $entry,
		string $csvSeparator,
		callable $successCallback,
		callable $errorCallback
	) {
		if(!file_exists($path)) {
			$this->fillDatasetErrors($entry, $noPathMessage, $errorCallback);
			return;
		}
		
		$writeString = '';
		foreach($entry->data as $data) {
			$writeString .= "\n".Main::arrayToCSV($data, $csvSeparator);
		}
		
		$this->writeDataSetFile($path, $writeString, $entry, $successCallback, $errorCallback);
	}
	
	public function saveWebAccessDataSet(int $studyId, int $timestamp, string $pageName, string $referer, string $userAgent): bool {
		return file_put_contents(
				PathsFS::fileResponses($studyId, PathsFS::FILENAME_WEB_ACCESS),
				"\n\"".$timestamp."\";\"$pageName\";\"$referer\";\"$userAgent\"",
				FILE_APPEND | LOCK_EX
			) !== false;
	}
	public function saveDataSetCache(string $userId, DataSetCache $cache, callable $successProgressCallback, callable $errorProgressCallback) {
		foreach($cache->getStatisticsCache() as $studyId => $entry) {
			$pathStatisticsNewData = PathsFS::fileStatisticsNewData($studyId);
			
			$writeString = '';
			foreach($entry->data as $data) {
				$writeString .= "\n" .StatisticsNewDataSetEntryLoader::export($data);
			}
			
			$this->writeDataSetFile($pathStatisticsNewData, $writeString, $entry, $successProgressCallback, $errorProgressCallback);
		}
		
		$csvSeparator = Configs::get('csv_delimiter');
		foreach($cache->getQuestionnaireCache() as $studyId => $questionnaireEntries) {
			foreach($questionnaireEntries as $questionnaireId => $entry) {
				$this->writeCsv(
					PathsFS::fileResponses($studyId, $questionnaireId),
					"Questionnaire (id=$questionnaireId) does not exist",
					$entry,
					$csvSeparator,
					$successProgressCallback,
					$errorProgressCallback
				);
			}
		}
		
		foreach($cache->getEventCache() as $studyId => $entry) {
			$this->writeCsv(
				PathsFS::fileResponses($studyId, PathsFS::FILENAME_EVENTS),
				"Study $studyId seems to be broken",
				$entry,
				$csvSeparator,
				$successProgressCallback,
				$errorProgressCallback
			);
		}
		
		foreach($cache->getFileCache() as $datasetId => $entry) {
			$path = PathsFS::filePendingUploads($entry->studyId, $userId, $entry->identifier);
			if(!file_put_contents($path, $entry->internalPath, LOCK_EX))
				$errorProgressCallback($datasetId, 'Internal Server Error: Saving failed');
		}
	}
	public function uploadFile(int $studyId, string $userId, int $identifier, FileUploader $fileUploader) {
		$waitingPath = PathsFS::filePendingUploads($studyId, $userId, $identifier);
		if(!file_exists($waitingPath))
			throw new CriticalException('Not allowed');
		
		$targetPath = file_get_contents($waitingPath);
		if(!$targetPath)
			throw new CriticalException('Internal server error');
		
		if(file_exists($targetPath))
			throw new CriticalException('File already exists');
		
		if(!$fileUploader->upload($targetPath) || !unlink($waitingPath))
			throw new CriticalException('Uploading failed');
		
		$mediaZipPath = Paths::fileMediaZip($studyId);
		if(file_exists($mediaZipPath))
			unlink($mediaZipPath);
	}
	
	public function getLastResponseTimestampOfStudies(): array {
		$lastActivities = [];
		$handle = opendir(PathsFS::folderStudies());
		while($studyId = readdir($handle)) {
			if($studyId[0] === '.' || $studyId === PathsFS::FILENAME_STUDY_INDEX)
				continue;
			
			$path = PathsFS::fileResponses($studyId, PathsFS::FILENAME_EVENTS);
			$lastActivities[$studyId] = filemtime($path);
		}
		closedir($handle);
		return $lastActivities;
	}
	
	private function fillMediaFolder(ZipArchive $zip, $path, callable $getMediaFilename, int $startCount, int $totalCount) {
		$handle = opendir($path);
		$count = $startCount;
		while($file = readdir($handle)) {
			if($file[0] != '.') {
				$zip->addFile("$path/$file", $getMediaFilename($file));

				$count++;
				$percent = round(($count / $totalCount) * 100);
				echo "event: progress\n";
				echo "data: $percent\n\n";
				if (ob_get_contents())
					ob_end_flush();
				flush();
			}
		}
		closedir($handle);
	}
	public function createMediaZip(int $studyId) {
		$pathZip = Paths::fileMediaZip($studyId);
		$zip = new ZipArchive();
		$zip->open($pathZip, ZIPARCHIVE::CREATE);
		$folderImages = Paths::folderImages($studyId);
		$folderAudio = Paths::folderAudio($studyId);
		$countImages = count(scandir($folderImages)) - 2;
		$countAudio = count(scandir($folderAudio)) - 2;
		$countTotal = $countImages + $countAudio;

		echo "event: progress\n";
		echo "data: 0\n\n";
		if (ob_get_contents())
			ob_end_flush();
		flush();

		$this->fillMediaFolder(
			$zip,
			Paths::folderImages($studyId),
			function($fileName) { return Paths::publicFileImageFromMediaFilename($fileName); },
			0,
			$countTotal
		);
		$this->fillMediaFolder(
			$zip,
			Paths::folderAudio($studyId),
			function($fileName) { return Paths::publicFileAudioFromMediaFilename($fileName); },
			$countImages,
			$countTotal
		);
		
		$zip->close();
	}
	public function outputResponsesFile(int $studyId, string $identifier) {
		$path = PathsFS::fileResponses($studyId, $identifier);
		if(!file_exists($path))
			throw new CriticalException("$path does not exist");
		
		Main::setHeader('Content-Type: text/csv');
		readfile($path);
	}
	
	/**
	 * @throws CriticalException
	 */
	private function outputMediaFromResponses(string $path, string $contentType) {
		if(!file_exists($path))
			throw new CriticalException("$path does not exist");
		
		Main::setHeader("Content-Type: $contentType");
		Main::setHeader('Content-Length: '.filesize($path));
		readfile($path);
	}
	public function outputImageFromResponses(int $studyId, string $userId, int $entryId, string $key) {
		$this->outputMediaFromResponses(Paths::fileImageFromData($studyId, $userId, $entryId, $key), 'image/png');
	}
	public function outputAudioFromResponses(int $studyId, string $userId, int $entryId, string $key) {
		$this->outputMediaFromResponses(Paths::fileAudioFromData($studyId, $userId, $entryId, $key), 'video/3gpp');
	}
	public function getResponseFilesList(int $studyId): array {
		$list = [];
		$events_file = PathsFS::FILENAME_EVENTS.'.csv';
		$webAccess_file = PathsFS::FILENAME_WEB_ACCESS.'.csv';
		$path = opendir(PathsFS::folderResponses($studyId));
		while($file = readdir($path)) {
			if($file[0] != '.' && $file != $events_file && $file != $webAccess_file) {
				$list[] = substr($file, 0, -4);
			}
		}
		return $list;
	}
	
}