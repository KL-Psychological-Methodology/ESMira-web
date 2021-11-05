<?php

namespace backend;

class Files {
	const FILENAME_DATA = 'esmira_data',
		FILENAME_STATISTICS_METADATA = '.metadata',
		FILENAME_STATISTICS_NEWLINES = '.new_data',
		FILENAME_STATISTICS_JSONFILE = 'json',
		FILENAME_STUDY_INDEX = '.index',
		FILENAME_WEB_ACCESS = 'web_access',
		FILENAME_EVENTS = 'events',
		FILE_CONFIG = DIR_BASE.'backend/config/configs.php',
		FILE_DEFAULT_CONFIG = DIR_BASE.'backend/config/configs.default.php';
		
	
	static function get_folder_data() {
		return Configs::get('dataFolder_path');
	}
	static function get_folder_errorReports() {
		return self::get_folder_data() .'errors/';
	}
	static function get_folder_studies() {
		return self::get_folder_data() .'studies/';
	}
	static function get_folder_legal() {
		return self::get_folder_data() .'legal/';
	}
	static function get_folder_tokenRoot() {
		return self::get_folder_data() .'.loginToken/';
	}
	static function get_file_logins() {
		return self::get_folder_data() .'.logins';
	}
	static function get_file_permissions() {
		return self::get_folder_data() .'.permissions';
	}
	static function get_file_serverStatistics() {
		return self::get_folder_data() .'server_statistics.json';
	}
	static function get_file_studyIndex() {
		return self::get_folder_studies() .self::FILENAME_STUDY_INDEX;
	}
	
	
	static function get_folder_study($study_id) {
		$study_id = (int) $study_id;
		return self::get_folder_studies() ."$study_id/";
	}
	static function get_folder_langs($study_id) {
		$study_id = (int) $study_id;
		return self::get_folder_studies() ."$study_id/.langs/";
	}
	static function get_folder_messages($study_id) {
		$study_id = (int) $study_id;
		return self::get_folder_studies() ."$study_id/.messages/";
	}
	static function get_folder_messages_archive($study_id) {
		return self::get_folder_messages($study_id) .".archive/";
	}
	static function get_folder_messages_pending($study_id) {
		return self::get_folder_messages($study_id) .".pending/";
	}
	static function get_folder_messages_unread($study_id) {
		return self::get_folder_messages($study_id) .".unread/";
	}
	static function get_folder_responsesIndex($study_id) {
		$study_id = (int) $study_id;
		return self::get_folder_studies() ."$study_id/.responses_index/";
	}
	static function get_folder_responses($study_id) {
		$study_id = (int) $study_id;
		return self::get_folder_studies() ."$study_id/responses/";
	}
	static function get_folder_statistics($study_id) {
		$study_id = (int) $study_id;
		return self::get_folder_studies() ."$study_id/.statistics/";
	}
	static function get_folder_userData($study_id) {
		$study_id = (int) $study_id;
		return self::get_folder_studies() ."$study_id/.userdata/";
	}
	static function get_folder_token($user) {
		return self::get_folder_tokenRoot() .Files::make_urlFriendly($user).'/';
	}
	
	static function interpret_errorReport_file($filename) {
		if(substr($filename, 0, 1) === '_') {
			$seen = true;
			$filename = substr($filename, 1);
		}
		else
			$seen = false;
		$parts = explode('-', $filename);
		
		return [
			'seen' => $seen,
			'note' => (count($parts) === 2) ? self::get_urlFriendly($parts[1]) : '',
			'timestamp' => (int) $parts[0]
		];
	}
	static function get_file_errorReport($timestamp, $note=false, $seen=false) {
		$path = self::get_folder_errorReports();
		if($seen)
			$path .= '_';
		$path .= ((int)$timestamp);
		if($note)
			$path .= '-'.self::make_urlFriendly($note);
		return $path;
	}
	static function get_file_studyConfig($study_id) {
		$study_id = (int) $study_id;
		return self::get_folder_studies()."$study_id/.config.json";
	}
	static function get_file_langConfig($study_id, $code) {
		return self::get_folder_langs($study_id)."/$code.json";
	}
	static function get_file_studyMetadata($study_id) {
		$study_id = (int) $study_id;
		return self::get_folder_studies()."$study_id/.metadata";
	}
	static function get_file_responses($study_id, $questionnaire_identifier) {
		switch($questionnaire_identifier) {
			case self::FILENAME_EVENTS:
				$filename = self::FILENAME_EVENTS;
				break;
			case self::FILENAME_WEB_ACCESS:
				$filename = self::FILENAME_WEB_ACCESS;
				break;
			default:
				$filename = (int) $questionnaire_identifier;
				break;
				
		}
		return self::get_folder_responses($study_id) ."$filename.csv";
	}
	static function get_file_responsesIndex($study_id, $questionnaire_identifier) {
		return self::get_folder_responsesIndex($study_id) .$questionnaire_identifier;
	}
	static function get_file_responsesBackup($study_id, $questionnaire_identifier) {
		$date = date('o-m-d');
		$folder = self::get_folder_responses($study_id);
		$filename = "{$date}_$questionnaire_identifier";
		$file = $folder ."$filename.csv";
		
		
		$count = 2;
		while(file_exists($file)) {
			$file = "$folder{$date}_{$count}_$questionnaire_identifier.csv";
			if(++$count > Configs::get('max_possible_backups_per_day'))
				Output::error('Could not rename old datafile. There are too many copies. Aborting... Check your datafiles before trying again.');
		}
		
		return $file;
	}
	static function get_file_statisticsMetadata($study_id) {
		return self::get_folder_statistics($study_id).self::FILENAME_STATISTICS_METADATA;
	}
	static function get_file_statisticsNewData($study_id) {
		return self::get_folder_statistics($study_id).self::FILENAME_STATISTICS_NEWLINES;
	}
	static function get_file_statisticsJson($study_id) {
		return self::get_folder_statistics($study_id).self::FILENAME_STATISTICS_JSONFILE;
	}
	static function get_file_userData($study_id, $user) {
		return self::get_folder_userData($study_id) .self::make_urlFriendly($user);
	}
	static function get_file_message_pending($study_id, $user) {
		return self::get_folder_messages_pending($study_id) .self::make_urlFriendly($user);
	}
	static function get_file_message_archive($study_id, $user) {
		return self::get_folder_messages_archive($study_id) .self::make_urlFriendly($user);
	}
	static function get_file_message_unread($study_id, $user) {
		return self::get_folder_messages_unread($study_id) .self::make_urlFriendly($user);
	}
	static function get_file_lock($study_id) {
		$study_id = (int) $study_id;
		return self::get_folder_studies()."$study_id/.locked";
	}
	static function get_file_token($user, $hash) {
		return self::get_folder_token($user) .$hash;
	}
	static function get_file_tokenHistory($user, $num) {
		return self::get_folder_token($user) .'.history' .((int) $num);
	}
	static function get_file_blockLogin($user) {
		return self::get_folder_token($user) .".blocking";
	}
	static function get_file_langImpressum($code) {
		return $code === '_' ? self::get_folder_legal().'impressum.html' : self::get_folder_legal(). "impressum.$code.html";
	}
	static function get_file_langPrivacyPolicy($code) {
		return $code === '_' ? self::get_folder_legal().'privacy_policy.html' : self::get_folder_legal(). "privacy_policy.$code.html";
	}
	
	//For make_urlFriendly() and get_urlFriendly(), thanks to https://www.php.net/manual/en/function.base64-encode.php#123098
	private static function make_urlFriendly($s) {
		return str_replace(['+','/','='], ['-','_',''], base64_encode($s));
	}
	static function get_urlFriendly($s) {
		return base64_decode(str_replace(['-','_'], ['+','/'], $s));
	}
}