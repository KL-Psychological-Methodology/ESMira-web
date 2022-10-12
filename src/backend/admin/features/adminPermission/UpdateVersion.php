<?php

namespace backend\admin\features\adminPermission;

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\dataClasses\ErrorReportInfo;
use backend\dataClasses\Message;
use backend\dataClasses\StudyStatisticsEntry;
use backend\dataClasses\UserData;
use backend\DataSetCacheStatisticsEntry;
use backend\fileSystem\DataStoreFS;
use backend\fileSystem\loader\ErrorReportInfoLoader;
use backend\fileSystem\loader\MessagesArchivedLoader;
use backend\fileSystem\loader\MessagesPendingLoader;
use backend\fileSystem\loader\MessagesUnreadLoader;
use backend\fileSystem\loader\ResponsesIndexLoader;
use backend\fileSystem\loader\StatisticsNewDataSetEntryLoader;
use backend\fileSystem\loader\UserDataLoader;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\Main;
use backend\exceptions\PageFlowException;
use backend\Paths;
use backend\ResponsesIndex;
use stdClass;
use Throwable;

class UpdateVersion extends DoUpdate {
	
	protected function versionIsHigher($oldVersionString, $newVersionString): bool {
		$matchOld = preg_match("/(\d+)\.(\d+)\.(\d+)\D*(\d*)/", $oldVersionString, $integersOld);
		$matchNew = preg_match("/(\d+)\.(\d+)\.(\d+)\D*(\d*)/", $newVersionString, $integersNew);
		
		return $matchOld && $matchNew &&
			(
				$integersNew[1] > $integersOld[1]
				|| (
					$integersNew[1] === $integersOld[1]
					&& (
						$integersNew[2] > $integersOld[2]
						|| (
							$integersNew[2] === $integersOld[2]
							&& (
								$integersNew[3] > $integersOld[3]
								|| (
									$integersNew[3] === $integersOld[3] && $integersNew[4] && $integersNew[4] > $integersOld[4]
								)
							)
						)
					)
				)
			);
	}
	
	/**
	 * @throws CriticalException
	 */
	function runUpdateScript(string $fromVersion) {
		if($this->versionIsHigher($fromVersion, '2.0.0')) {
			$changeResponsesIndex = function(int $studyId, string $identifier) {
				$values = unserialize(file_get_contents(PathsFS::fileResponsesIndex($studyId, $identifier)));
				if(!$values)
					throw new CriticalException("Could not unserialize responsesIndex $identifier for study $studyId");
				if(!is_array($values)) //skip if data is already in new format
					return;
				if(!isset($values['keys']))
					$values = ['keys' => $values, 'types' => []];
				
				ResponsesIndexLoader::exportFile($studyId, $identifier, new ResponsesIndex($values['keys'], $values['types']));
			};
			
			$changeMessageFormat = function(string $path): array {
				$newMessages = [];
				$oldMessages = file_exists($path)
					? unserialize(file_get_contents($path))
					: [];
				
				foreach($oldMessages as $message) {
					if(!is_array($message)) { //skip if data is already in new format
						$newMessages[] = $message;
						continue;
					}
					
					$newMessage = new Message(
						$message['from'],
						$message['content'],
						$message['pending'] ?? false,
						$message['unread'] ?? false,
						$message['archived'] ?? false
					);
					$newMessage->read = isset($message['read']) ? $message['read']*1000 : 0;
					$newMessage->sent = $message['sent'] ?? 0;
					$newMessage->delivered = $message['delivered'] ?? 0;
					$newMessages[] = $newMessage;
				}
				return $newMessages;
			};
			
			
			//
			//Replace error report info format from info in filename to additional info file
			//
			
			if(!file_exists(PathsFS::fileErrorReportInfo())) { //skip if data is already in new format
				$folderErrorReports = PathsFS::folderErrorReports();
				$handle = opendir($folderErrorReports);
				$errorInfoArray = [];
				while($filename = readdir($handle)) {
					if($filename[0] == '.')
						continue;
					if(substr($filename, 0, 1) === '_') {
						$seen = true;
						$filenameForSplit = substr($filename, 1);
					} else {
						$seen = false;
						$filenameForSplit = $filename;
					}
					$parts = explode('-', $filenameForSplit);
					
					$note = (count($parts) === 2) ? Paths::getFromUrlFriendly($parts[1]) : '';
					$timestamp = (int)$parts[0];
					
					$errorInfoArray[$timestamp] = new ErrorReportInfo($timestamp, $note, $seen);
					rename($folderErrorReports . $filename, PathsFS::fileErrorReport($timestamp));
				}
				ErrorReportInfoLoader::exportFile($errorInfoArray);
				closedir($handle);
			}
			
			//php has still loaded the old Configs so we have to get our store class manually
			$dataStore = new DataStoreFS();
			$studyStore = $dataStore->getStudyStore();
			$messagesStore = $dataStore->getMessagesStore();
			
			foreach($studyStore->getStudyIdList() as $studyId) {
				try {
					//
					//replace format in PathsFS::fileStatisticsNewData() from "a|b|c" to [a,b,c]
					//
					
					$path = PathsFS::fileStatisticsNewData($studyId);
					if(file_exists($path)) {
						$newDataCollection = file($path);
						
						$newContent = '';
						foreach($newDataCollection as $line) {
							$newData = explode('|', trim($line));
							if(count($newData) != 4) { //skip if line is empty or data is already in new format
								$newContent .= "\n" . trim($line);
								continue;
							}
							list($key, $index, $timestamp, $answer) = $newData;
							$entry = new DataSetCacheStatisticsEntry($key, $index, $timestamp, $answer);
							$newContent .= "\n" . StatisticsNewDataSetEntryLoader::export($entry);
						}
						file_put_contents($path, $newContent, LOCK_EX);
					}
					
					
					//
					//replace format in PathsFS::fileStudyStatisticsMetadata() from {stdClass} to [StudyStatisticsEntry]
					//
					
					$path = PathsFS::fileStudyStatisticsMetadata($studyId);
					$oldMetadata = file_exists($path)
						? unserialize(file_get_contents($path))
						: new stdClass();
					$newMetadata = [];
					
					//these loops are also compatible with the new format:
					foreach($oldMetadata as $key => $metadataCollection) {
						$newMetadata[$key] = [];
						foreach($metadataCollection as $i => $metadata) {
							$newMetadata[$key][$i] = new StudyStatisticsEntry(
								$metadata->conditions,
								$metadata->conditionType,
								$metadata->storageType,
								$metadata->defaultTimeInterval ?? $metadata->timeInterval
							);
						}
					}
					FileSystemBasics::writeFile($path, serialize($newMetadata));
					
					
					//
					//replace format for responsesIndex from [[], []] to ResponsesIndex
					//
					
					$study = $studyStore->getStudyConfig($studyId);
					foreach($study->questionnaires as $questionnaire) {
						$changeResponsesIndex($studyId, (string)$questionnaire->internalId);
					}
					$changeResponsesIndex($studyId, PathsFS::FILENAME_EVENTS);
					$changeResponsesIndex($studyId, PathsFS::FILENAME_WEB_ACCESS);
					
					
					//
					//replace format for userData from AArray to UserData
					//
					foreach($studyStore->getStudyParticipants($studyId) as $userId) {
						try {
							$path = PathsFS::fileUserData($studyId, $userId);
							$array = unserialize(file_get_contents($path));
							
							if(!$array)
								throw new CriticalException("Could not unserialize userdata $userId for study $studyId");
							if(!is_array($array))//skip if data is already in new format
								continue;
							
							$userdata = new UserData(
								$array['userId'] ?? -1,
								$array['token'] ?? '',
									(int) ($array['dataSetCount'] ?? 0),
								$array['group'] ?? 0,
								$array['appType'] ?? '',
								$array['appVersion'] ?? '0'
							);
							file_put_contents($path, UserDataLoader::export($userdata));
						}
						catch(Throwable $e) {
							throw new PageFlowException("Failed updating user data for $userId", 0, $e);
						}
					}
					
					
					//
					//replace format for messages from [AArray] to [Message]
					//
					
					foreach($messagesStore->getParticipantsWithMessages($studyId) as $msgInfo) {
						try {
							$userId = $msgInfo->name;
							MessagesArchivedLoader::exportFile(
								$studyId,
								$userId,
								$changeMessageFormat(PathsFS::fileMessageArchive($studyId, $userId))
							);
							MessagesPendingLoader::exportFile(
								$studyId,
								$userId,
								$changeMessageFormat(PathsFS::fileMessagePending($studyId, $userId))
							);
							MessagesUnreadLoader::exportFile(
								$studyId,
								$userId,
								$changeMessageFormat(PathsFS::fileMessageUnread($studyId, $userId))
							);
						}
						catch(Throwable $e) {
							throw new CriticalException("Failed updating messages for $userId", 0, $e);
						}
					}
				}
				catch(Throwable $e) {
					throw new CriticalException("Failed updating study $studyId\n$e", 0, $e);
				}
			}
		}
		else if($this->versionIsHigher($fromVersion, '2.0.4')) { //these changes done directly in $fromVersion <= 200
			$folderErrorReports = PathsFS::folderErrorReports();
			$handle = opendir($folderErrorReports);
			$path = $folderErrorReports .".error_info"; //we cannot use ErrorReportInfoLoader::importFile because we are still using the old version of PathFS
			$errorInfoArray = file_exists($path) ? unserialize(file_get_contents($path)) : []; //expected to be []; if data is already in new format we just resave everything
			while($filename = readdir($handle)) {
				if($filename[0] == '.')
					continue;
				
				$timestamp = (int) $filename;
				
				if(isset($errorInfoArray[$timestamp]))
					continue;
				$oldErrorInfoPath = "$folderErrorReports.$filename.info";
				
				if(file_exists($oldErrorInfoPath)) {
					$errorInfoArray[$timestamp] = unserialize(file_get_contents($oldErrorInfoPath));
					unlink($oldErrorInfoPath);
				}
				else
					$errorInfoArray[$timestamp] = new ErrorReportInfo($timestamp);
			}
			FileSystemBasics::writeFile($path, serialize($errorInfoArray));
			closedir($handle);
		}
		
		if($this->versionIsHigher($fromVersion, '2.0.4')) {
			//prevent automatic logout:
			
			if(isset($_COOKIE['user'])) {
				Main::setCookie('account', $_COOKIE['user']);
				Main::deleteCookie('user');
			}
			if(isset($_SESSION['user']))
				$_SESSION['account'] = $_SESSION['user'];
		}
	}
	
	/**
	 * @throws CriticalException
	 * @throws PageFlowException
	 */
	function exec(): array {
		if(!isset($_GET['fromVersion']))
			throw new PageFlowException('Missing data');
		
		
		try {
			$this->runUpdateScript($_GET['fromVersion']);
		}
		catch(Throwable $e) {
			throw $this->revertUpdate("Error while running update script. Reverting... \n$e");
		}
		
		
		//cleaning up
		if(file_exists($this->folderPathBackup) && (!FileSystemBasics::emptyFolder($this->folderPathBackup) || !@rmdir($this->folderPathBackup)))
			throw new PageFlowException("Failed to clean up backup. The update was successful. But please delete this folder and check its contents manually: $this->folderPathBackup");
		return [];
	}
}