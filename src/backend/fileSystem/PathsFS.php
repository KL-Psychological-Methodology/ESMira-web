<?php

namespace backend\fileSystem;

use backend\Main;
use backend\Configs;
use backend\CriticalError;
use backend\Paths;

class PathsFS {
	const FILENAME_DATA = 'esmira_data',
		FILENAME_EVENTS = 'events',
		FILENAME_STATISTICS_JSONFILE = 'json',
		FILENAME_STATISTICS_METADATA = '.metadata',
		FILENAME_STATISTICS_NEWLINES = '.new_data',
		FILENAME_STUDY_INDEX = '.index',
		FILENAME_WEB_ACCESS = 'web_access';
		
	static function folderData(): string {
		return Configs::get('dataFolder_path');
	}
	static function folderErrorReports(): string {
		return self::folderData() .'errors/';
	}
	static function folderLegal(): string {
		return self::folderData() .'legal/';
	}
	static function folderStudies(): string {
		return self::folderData() .'studies/';
	}
	static function folderTokenRoot(): string {
		return self::folderData() .'.loginToken/';
	}
	
	static function fileLogins(): string {
		return self::folderData() .'.logins';
	}
	static function filePermissions(): string {
		return self::folderData() .'.permissions';
	}
	static function fileServerStatistics(): string {
		return self::folderData() .'server_statistics.json';
	}
	static function fileStudyIndex(): string {
		return self::folderStudies() .self::FILENAME_STUDY_INDEX;
	}
	
	
	static function folderLangs(int $studyId): string {
		return self::folderStudies() ."$studyId/.langs/";
	}
	static function folderMedia(int $studyId): string {
		return self::folderStudies() ."$studyId/media/";
	}
	static function folderMessages(int $studyId): string {
		return self::folderStudies() ."$studyId/.messages/";
	}
	static function folderMessagesArchive(int $studyId): string {
		return self::folderMessages($studyId) .".archive/";
	}
	static function folderMessagesPending(int $studyId): string {
		return self::folderMessages($studyId) .".pending/";
	}
	static function folderMessagesUnread(int $studyId): string {
		return self::folderMessages($studyId) .".unread/";
	}
	static function folderPendingUploads(int $studyId): string {
		return self::folderMedia($studyId) .'.pending_uploads/';
	}
	static function folderResponsesIndex(int $studyId): string {
		return self::folderStudies() ."$studyId/.responses_index/";
	}
	static function folderResponses(int $studyId): string {
		return self::folderStudies() ."$studyId/responses/";
	}
	static function folderStatistics(int $studyId): string {
		return self::folderStudies() ."$studyId/.statistics/";
	}
	static function folderStudy(int $studyId): string {
		return self::folderStudies() ."$studyId/";
	}
	static function folderToken(string $accountName): string {
		return self::folderTokenRoot() . Paths::makeUrlFriendly($accountName) .'/';
	}
	static function folderUserData(int $studyId): string {
		return self::folderStudies() ."$studyId/.userdata/";
	}
	
	
	static function fileBlockLogin(string $accountName): string {
		return self::folderToken($accountName) .".blocking";
	}
	static function fileErrorReport(int $timestamp): string {
		return self::folderErrorReports() .$timestamp;
	}
	static function fileErrorReportInfo(): string {
		return self::folderErrorReports() .".error_info";
	}
	static function fileLangConfig(int $studyId, string $code): string {
		return self::folderLangs($studyId)."/$code.json";
	}
	static function fileLangImpressum(string $code): string {
		return $code === '_' ? self::folderLegal().'impressum.html' : self::folderLegal(). "impressum.$code.html";
	}
	static function fileLangPrivacyPolicy(string $code): string {
		return $code === '_' ? self::folderLegal().'privacy_policy.html' : self::folderLegal(). "privacy_policy.$code.html";
	}
	static function fileLock(int $studyId): string {
		return self::folderStudies()."$studyId/.locked";
	}
	static function fileMessageArchive(int $studyId, string $userId): string {
		return self::folderMessagesArchive($studyId) . Paths::makeUrlFriendly($userId);
	}
	static function fileMessagePending(int $studyId, string $userId): string {
		return self::folderMessagesPending($studyId) . Paths::makeUrlFriendly($userId);
	}
	static function fileMessageUnread(int $studyId, string $userId): string {
		return self::folderMessagesUnread($studyId) . Paths::makeUrlFriendly($userId);
	}
	static function filePendingUploads(int $studyId, string $userId, int $identifier): string {
		return self::folderPendingUploads($studyId) . Paths::makeUrlFriendly($userId) .'_' .$identifier;
	}
	static function fileResponses(int $studyId, string $questionnaire_identifier): string {
		switch($questionnaire_identifier) {
			case self::FILENAME_EVENTS:
				$filename = self::FILENAME_EVENTS;
				break;
			case self::FILENAME_WEB_ACCESS:
				$filename = self::FILENAME_WEB_ACCESS;
				break;
			default:
				if(Main::strictCheckInput($questionnaire_identifier))
					$filename = $questionnaire_identifier;
				else
					$filename = 'error';
				break;
			
		}
		return self::folderResponses($studyId) ."$filename.csv";
	}
	/**
	 * @throws CriticalError
	 */
	static function fileResponsesBackup(int $studyId, string $questionnaire_identifier): string {
		$date = date('o-m-d');
		$folder = self::folderResponses($studyId);
		$filename = "{$date}_$questionnaire_identifier";
		$file = $folder ."$filename.csv";
		
		
		$count = 2;
		while(file_exists($file)) {
			$file = "$folder{$date}_{$count}_$questionnaire_identifier.csv";
			if(++$count > Configs::get('max_possible_backups_per_day'))
				throw new CriticalError('Could not rename old datafile. There are too many copies. Aborting... Check your datafiles before trying again.');
		}
		
		return $file;
	}
	static function fileResponsesIndex(int $studyId, string $questionnaire_identifier): string {
		return self::folderResponsesIndex($studyId) .$questionnaire_identifier;
	}
	static function fileStatisticsJson(int $studyId): string {
		return self::folderStatistics($studyId).self::FILENAME_STATISTICS_JSONFILE;
	}
	static function fileStudyStatisticsMetadata(int $studyId): string {
		return self::folderStatistics($studyId).self::FILENAME_STATISTICS_METADATA;
	}
	static function fileStatisticsNewData(int $studyId): string {
		return self::folderStatistics($studyId).self::FILENAME_STATISTICS_NEWLINES;
	}
	static function fileStudyConfig(int $studyId): string {
		return self::folderStudies()."$studyId/.config.json";
	}
	static function fileStudyMetadata(int $studyId): string {
		return self::folderStudies()."$studyId/.metadata";
	}
	static function fileToken(string $accountName, string $hash): string {
		return self::folderToken($accountName) .$hash;
	}
	static function fileTokenHistory(string $accountName, int $num): string {
		return self::folderToken($accountName) .'.history' .$num;
	}
	static function fileUserData(int $studyId, string $userId): string {
		return self::folderUserData($studyId) . Paths::makeUrlFriendly($userId);
	}
}