<?php
declare(strict_types=1);

namespace backend\fileSystem\subStores;

use backend\dataClasses\RewardCodeData;
use backend\exceptions\NoRewardCodeException;
use backend\fileSystem\loader\RewardCodeDataLoader;
use backend\Main;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\Paths;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\fileSystem\loader\ResponsesIndexLoader;
use backend\ResponsesIndex;
use backend\subStores\StatisticsStoreWriter;
use backend\subStores\StudyStore;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use stdClass;

require_once DIR_BASE . 'backend/responseFileKeys.php';

class StudyStoreFS implements StudyStore {
	/**
	 * @throws CriticalException
	 */
	private function copyResponseFile(int $studyId, string $identifier) {
		$file_name = PathsFS::fileResponses($studyId, $identifier);
		$file_backupName = PathsFS::fileResponsesBackup($studyId, $identifier);
		
		if(!copy($file_name, $file_backupName))
			throw new CriticalException("Copying $file_name to $file_backupName failed");
	}
	
	/**
	 * @throws CriticalException
	 */
	private function correctResponseFile(
		array          $newValuesIndex,
		ResponsesIndex $oldKeys,
		ResponsesIndex $newKeys,
		string         $pathResponses,
		string         $pathResponsesBackup,
		int      	   $studyId,
		string         $identifier
	) {
		//we read the backup and create a new responses file from that:
		
		$csvDelimiter = Configs::get('csv_delimiter');
		$handleNewResponses = fopen($pathResponses, 'w');
		$handleBackup = fopen($pathResponsesBackup, 'r');
		
		if(!$handleNewResponses || !$handleBackup)
			throw new CriticalException("Could not open $pathResponses or $pathResponsesBackup");
		
		flock($handleNewResponses, LOCK_EX);
		
		fgets($handleBackup); //skip first line - this is the old header. We dont need it
		
		
		if(feof($handleBackup)) { //there is no data. So we can just use the new headers
			//we use $newKeys because it has the correct sorting
			fputs($handleNewResponses, Main::arrayToCSV($newKeys->keys, $csvDelimiter));
			ResponsesIndexLoader::exportFile($studyId, $identifier, $newKeys);
			unlink($pathResponsesBackup); //there is no point in keeping this backup
		}
		else {
			$addedContent = '';
			foreach($newValuesIndex as $value) { //Adding new keys to the end of $oldKey and preparing and $addedContent
				$addedContent .= $csvDelimiter .'""';
				$oldKeys->addName($value);
			}
			
			fputs($handleNewResponses, Main::arrayToCSV($oldKeys->keys, $csvDelimiter));
			
			while(($line = fgets($handleBackup)) !== false) {
				fputs($handleNewResponses, "\n".rtrim($line, "\n").$addedContent);
			}
			
			ResponsesIndexLoader::exportFile($studyId, $identifier, $oldKeys);
		}
		
		
		fflush($handleNewResponses);
		flock($handleNewResponses, LOCK_UN);
		fclose($handleNewResponses);
		fclose($handleBackup);
	}
	
	/**
	 * @throws CriticalException
	 */
	private function writeIndexAndResponsesFiles(stdClass $study, string $identifier, ResponsesIndex $questionnaireIndex) {
		//Note: When there is already data:
		// If keys are removed, they will stay in the headers
		// if keys are changed or new, they will be added to the end
		
		if(isset($study->randomGroups) && $study->randomGroups >= 0 && $identifier !== PathsFS::FILENAME_WEB_ACCESS)
			array_splice($questionnaireIndex->keys, 0, 0, 'group');
		
		$studyId = (int) $study->id;
		$pathResponses = PathsFS::fileResponses($studyId, $identifier);
		$pathIndex = PathsFS::fileResponsesIndex($studyId, $identifier);
		
		//if file does not exist we can just create them and be done with it:
		if(!file_exists($pathResponses) || !file_exists($pathIndex)) {
			$csvDelimiter = Configs::get('csv_delimiter');
			FileSystemBasics::writeFile($pathResponses, Main::arrayToCSV($questionnaireIndex->keys, $csvDelimiter));
			ResponsesIndexLoader::exportFile($studyId, $identifier, $questionnaireIndex);
			return;
		}
		$oldIndex = ResponsesIndexLoader::importFile($studyId, $identifier);
		$oldIndex->types = $questionnaireIndex->types;
		
		//finding out if there are new headers:
		$index = [];
		foreach($questionnaireIndex->keys as $value) {
			$index[$value] = $value;
		}
		foreach($oldIndex->keys as $value) {
			unset($index[$value]);
		}
		
		if(empty($index)) {//no new headers
			//save types in case they have changed (but we need to use oldIndex in case order of items is different)
			$oldIndex->types = $questionnaireIndex->types;
			ResponsesIndexLoader::exportFile($studyId, $identifier, $oldIndex);
			return;
		}
		
		$pathResponsesBackup = PathsFS::fileResponsesBackup($studyId, $identifier);
		
		//move responses to a backup:
		if(rename($pathResponses, $pathResponsesBackup))
			chmod($pathResponsesBackup, 0666);
		else
			throw new CriticalException("Could not rename $pathResponses to $pathResponsesBackup");
		
		//if the file is too big to be changed on the fly, we just create a new file and that's it:
		if(filesize($pathResponsesBackup) > Configs::get('max_filesize_for_changes')) {
			$csvDelimiter = Configs::get('csv_delimiter');
			FileSystemBasics::writeFile($pathResponses, Main::arrayToCSV($questionnaireIndex->keys, $csvDelimiter));
			ResponsesIndexLoader::exportFile($studyId, $identifier, $questionnaireIndex);
		}
		else
			$this->correctResponseFile($index, $oldIndex, $questionnaireIndex, $pathResponses, $pathResponsesBackup, $studyId, $identifier);
	}
	
	/**
	 * @throws CriticalException
	 */
	private function removeStudyFromPermissions($studyId) {
		$accountStore = Configs::getDataStore()->getAccountStore();
		
		$userList = $accountStore->getAccountList();
		foreach($userList as $accountName) {
			$accountStore->removeStudyPermission($accountName, $studyId, 'write');
			$accountStore->removeStudyPermission($accountName, $studyId, 'read');
			$accountStore->removeStudyPermission($accountName, $studyId, 'msg');
			$accountStore->removeStudyPermission($accountName, $studyId, 'publish');
		}
	}
	
	public function studyExists(int $studyId): bool {
		return file_exists(PathsFS::folderStudy($studyId));
	}
	public function isLocked(int $studyId): bool {
		return file_exists(PathsFS::fileLock($studyId));
	}
	function lockStudy(int $studyId, bool $lock=true) {
		$file = PathsFS::fileLock($studyId);
		
		if(file_exists($file)) {
			if(!$lock)
				unlink($file);
		}
		else {
			if($lock)
				file_put_contents($file, '1');
		}
	}
	
	public function getStudyLastChanged(int $studyId): int {
		$path = PathsFS::fileStudyConfig($studyId);
		return file_exists($path) ? filemtime($path) : -1;
	}
	
	public function getStudyIdList(): array {
		$studies = [];
		$handle = opendir(PathsFS::folderStudies());
		while($folderName = readdir($handle)) {
			$studyId = (int) $folderName;
			if($studyId != 0) {
				$studies[] = $studyId;
			}
		}
		closedir($handle);
		return $studies;
	}
	
	
	private function getDirectorySize($path){
		//thanks to https://stackoverflow.com/questions/478121/how-to-get-directory-size-in-php
		$bytestotal = 0;
		$path = realpath($path);
		if($path!==false && $path!='' && file_exists($path)){
			foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object){
				$bytestotal += $object->getSize();
			}
		}
		return $bytestotal;
	}
	
	public function getDirectorySizeOfStudies(): array {
		$directorySize = [];
		$folderStudies = PathsFS::folderStudies();
		$handle = opendir($folderStudies);
		while($folderName = readdir($handle)) {
			$studyId = (int) $folderName;
			if($studyId == 0)
				continue;
			
			$directorySize[$studyId] = $this->getDirectorySize("$folderStudies/$studyId");
		}
		closedir($handle);
		return $directorySize;
	}
	
	public function getStudyLangConfigAsJson(int $studyId, string $lang) {
		$path = PathsFS::fileLangConfig($studyId, $lang);
		return file_exists($path) ? file_get_contents($path) : $this->getStudyConfigAsJson($studyId);
	}
	public function getStudyConfigAsJson(int $studyId): string {
		$path = PathsFS::fileStudyConfig($studyId);
		if(!file_exists($path))
			throw new CriticalException("Study id $studyId does not exist");
		return file_get_contents($path);
	}
	public function getStudyConfig(int $studyId): stdClass {
		return json_decode($this->getStudyConfigAsJson($studyId));
	}
	public function getStudyLangConfig(int $studyId, string $lang): stdClass {
		return json_decode($this->getStudyLangConfigAsJson($studyId, $lang));
	}
	public function getAllLangConfigsAsJson(int $studyId): string {
		$path = PathsFS::folderLangs($studyId);
		$langBox = [];
		if(file_exists($path)) {
			$h_folder = opendir($path);
			while($file = readdir($h_folder)) {
				if($file[0] != '.') {
					$s = file_get_contents($path .$file);
					$key = explode('.', $file)[0];
					$langBox[] = "\"$key\":$s";
				}
			}
			closedir($h_folder);
		}
		return '{' .implode(',', $langBox) .'}';
	}
	public function getStudyParticipants(int $studyId): array {
		$path = PathsFS::folderUserData($studyId);
		$userIds = [];
		if(file_exists($path)) {
			$handle = opendir($path);
			while($file = readdir($handle)) {
				if($file[0] != '.') {
					$userIds[] = Paths::getFromUrlFriendly($file);
				}
			}
			closedir($handle);
		}
		return $userIds;
	}
	
	public function getEventIndex(int $studyId): ResponsesIndex {
		return ResponsesIndexLoader::importFile($studyId, PathsFS::FILENAME_EVENTS);
	}
	public function getQuestionnaireIndex(int $studyId, int $questionnaireId): ResponsesIndex {
		return ResponsesIndexLoader::importFile($studyId, (string) $questionnaireId);
	}
	
	public function questionnaireExists(int $studyId, int $questionnaireId): bool {
		$pathQuestionnaire = PathsFS::fileResponses($studyId, (string) $questionnaireId);
		return file_exists($pathQuestionnaire);
	}
	
	public function saveStudy(stdClass $studyCollection, array $questionnaireKeys) {
		$study = $studyCollection->_;
		$studyId = $study->id;
		
		//
		//create folders
		//
		FileSystemBasics::createFolder(PathsFS::folderStudy($studyId));
		FileSystemBasics::createFolder(PathsFS::folderLangs($studyId));
		FileSystemBasics::createFolder(PathsFS::folderUserData($studyId));
		FileSystemBasics::createFolder(PathsFS::folderStatistics($studyId));
		FileSystemBasics::createFolder(PathsFS::folderMessages($studyId));
		FileSystemBasics::createFolder(PathsFS::folderMedia($studyId));
		FileSystemBasics::createFolder(PathsFS::folderPendingUploads($studyId));
		FileSystemBasics::createFolder(Paths::folderImages($studyId));
		FileSystemBasics::createFolder(Paths::folderAudio($studyId));
		FileSystemBasics::createFolder(PathsFS::folderResponses($studyId));
		FileSystemBasics::createFolder(PathsFS::folderResponsesIndex($studyId));
		FileSystemBasics::createFolder(PathsFS::folderMessagesArchive($studyId));
		FileSystemBasics::createFolder(PathsFS::folderMessagesPending($studyId));
		FileSystemBasics::createFolder(PathsFS::folderMessagesUnread($studyId));
		FileSystemBasics::createFolder(PathsFS::folderRewardCodes($studyId));
		FileSystemBasics::createFolder(PathsFS::folderMerlinLogs($studyId));
		
		
		Configs::getDataStore()->getStudyStore()->lockStudy($studyId);
		try {
			//
			//save study configs
			//
			FileSystemBasics::emptyFolder(PathsFS::folderLangs($studyId)); //delete old language files
			
			$pathConfig = PathsFS::fileStudyConfig($studyId);
			foreach($studyCollection as $code => $s) {
				$studyJson = json_encode($s);
				FileSystemBasics::writeFile($code === '_' ? $pathConfig : PathsFS::fileLangConfig($studyId, $code), $studyJson);
			}
			
			//
			//save response files
			//
			if(isset($study->questionnaires)) {
				foreach($study->questionnaires as $q) {
					$index = $questionnaireKeys[$q->internalId];
					$index->keys = array_merge(KEYS_QUESTIONNAIRE_BASE_RESPONSES, $index->keys);
					$this->writeIndexAndResponsesFiles($study, (string)$q->internalId, $index);
				}
			}
			$this->writeIndexAndResponsesFiles($study, PathsFS::FILENAME_EVENTS, new ResponsesIndex(KEYS_EVENT_RESPONSES));
			$this->writeIndexAndResponsesFiles($study, PathsFS::FILENAME_WEB_ACCESS, new ResponsesIndex(KEYS_WEB_ACCESS));
			
			//
			//save study metadata
			//
			Configs::getDataStore()->getStudyMetadataStore($studyId)->updateMetadata($study);
		}
		finally {
			Configs::getDataStore()->getStudyStore()->lockStudy($studyId, false);
		}
	}
	
	public function backupStudy(int $studyId) {
		$study = $this->getStudyConfig($studyId);
		$metadata = Configs::getDataStore()->getStudyMetadataStore($studyId);
		
		foreach($study->questionnaires as $questionnaire) {
			$this->copyResponseFile($studyId, (string) $questionnaire->internalId);
		}
		
		$this->copyResponseFile($studyId, PathsFS::FILENAME_EVENTS);
		$this->copyResponseFile($studyId, PathsFS::FILENAME_WEB_ACCESS);
		$metadata->updateMetadata($study);
	}
	
	public function emptyStudy(int $studyId, array $questionnaireKeys) {
		FileSystemBasics::emptyFolder(PathsFS::folderResponses($studyId));
		FileSystemBasics::emptyFolder(PathsFS::folderStatistics($studyId));
		FileSystemBasics::emptyFolder(Paths::folderImages($studyId));
		FileSystemBasics::emptyFolder(Paths::folderAudio($studyId));
		FileSystemBasics::emptyFolder(PathsFS::folderPendingUploads($studyId));
		
		$mediaZip = Paths::fileMediaZip($studyId);
		if(file_exists($mediaZip))
			unlink($mediaZip);
		
		$study = $this->getStudyConfig($studyId);
		
		if(isset($study->questionnaires)) {
			foreach($study->questionnaires as $q) {
				$index = $questionnaireKeys[$q->internalId];
				$index->keys = array_merge(KEYS_QUESTIONNAIRE_BASE_RESPONSES, $index->keys);
				$this->writeIndexAndResponsesFiles($study, (string)$q->internalId, $index);
			}
		}
		$this->writeIndexAndResponsesFiles($study, PathsFS::FILENAME_EVENTS, new ResponsesIndex(KEYS_EVENT_RESPONSES));
		$this->writeIndexAndResponsesFiles($study, PathsFS::FILENAME_WEB_ACCESS, new ResponsesIndex(KEYS_WEB_ACCESS));
	}
	
	public function markStudyAsUpdated(int $studyId) {
		$study = $this->getStudyConfig($studyId);
		
		$study->version = isset($study->version) ? $study->version + 1 : 1;
		$study->subVersion = 0;
		$study->new_changes = false;
		FileSystemBasics::writeFile(PathsFS::fileStudyConfig($studyId), json_encode($study));
		
		foreach($study->langCodes as $langCode) {
			if($langCode == $study->lang)
				continue;
			$langStudy = $this->getStudyLangConfig($studyId, $langCode);
			$langStudy->version = $study->version;
			$langStudy->subVersion = 0;
			$langStudy->new_changes = false;
			FileSystemBasics::writeFile(PathsFS::fileLangConfig($studyId, $langCode), json_encode($langStudy));
		}
		
		
		Configs::getDataStore()->getStudyMetadataStore($studyId)->updateMetadata($study);
	}
	
	public function delete(int $studyId) {
		if(!$this->studyExists($studyId))
			return;
		$study = $this->getStudyConfig($studyId);
		$folderStudy = PathsFS::folderStudy($studyId);
		if(file_exists($folderStudy)) {
			FileSystemBasics::emptyFolder($folderStudy);
			if(!rmdir($folderStudy))
				throw new CriticalException("Could not remove $folderStudy");
		}
		else
			throw new CriticalException("$folderStudy does not exist!");
		
		$accessKeyStore = Configs::getDataStore()->getStudyAccessIndexStore();
		$accessKeyStore->removeStudy($studyId);
		$accessKeyStore->saveChanges();
		$this->removeStudyFromPermissions($studyId);
		
		if($study->published ?? false) {
			Configs::getDataStore()->getServerStatisticsStore()->update(function(StatisticsStoreWriter $statistics) {
				$statistics->decrementStudies();
			});
		}
	}
}