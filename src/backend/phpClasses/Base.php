<?php

namespace phpClasses;

use phpClasses\Files;
use phpClasses\StringFus;
use stdClass;

class Base {
	const SERVER_VERSION = 10,
	ACCEPTED_SERVER_VERSION = 7;
	
	static function get_milliseconds() {
		return function_exists('microtime') ? round(microtime(true) * 1000) : time() * 1000;
	}
	
	static function report($msg) {
		$filename = time();
		$location = Files::FOLDER_ERRORS.$filename.ERROR_FILE_EXTENSION;
		
		$num = 1;
		while(file_exists($location)) {
			$location = Files::FOLDER_ERRORS.(++$filename).ERROR_FILE_EXTENSION;
			if(++$num > 100)
				return false;
		}
		
		return file_put_contents($location, $msg) && chmod($location, 0666);
	}
	static function get_lang($default) {
		if(isset($_GET['lang'])) {
			$lang = $_GET['lang'];
			self::create_cookie('lang', $_GET['lang'], 32532447600);
		}
		else if(isset($_COOKIE['lang']))
			$lang = $_COOKIE['lang'];
		else if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		}
		else
			$lang = $default;
		
		switch($lang) {
			case 'de':
			case 'en':
				break;
			default:
				$lang = $default;
		}
		return $lang;
	}
	
	static function get_serverSettings() {
		if(!defined('SERVER_SETTINGS')) {
			if(file_exists(Files::FILE_SERVER_SETTINGS)) {
				require_once Files::FILE_SERVER_SETTINGS;
				return SERVER_SETTINGS;
			}
			else
				return DEFAULT_SERVER_SETTINGS;
		}
		else
			return SERVER_SETTINGS;
	}
	
	static function get_serverName() {
		$serverSettings = self::get_serverSettings();
		$lang = self::get_lang('_');
		$serverName_array = $serverSettings['serverName'];
		return isset($serverName_array[$lang]) ? $serverName_array[$lang] : ( isset($serverName_array['_']) ? $serverName_array['_'] : '');
	}
	
	static function get_accessKey() {
		if(isset($_GET['key']) && StringFus::check_input($_GET['key'])) {
			$key = strtolower(trim($_GET['key']));
			self::create_cookie('access_key', $key, 32532447600);
			if(strlen($key))
				return $key;
		}
		else if(isset($_COOKIE['access_key']) && StringFus::check_input($_COOKIE['access_key'])) {
			$key = strtolower($_COOKIE['access_key']);
			if(strlen($key))
				return $key;
		}
		
		return false;
	}
	
	static function save_webAccess($study_id, $pageName) {

//	$referer = isset($_SERVER["HTTP_REFERER"]) ? StringFu::strip_input($_SERVER["HTTP_REFERER"]) : '';
		$referer = isset($_SERVER["HTTP_REFERER"]) ? StringFus::strip_oneLineInput(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST)) : '';
		$user_agent = isset($_SERVER["HTTP_USER_AGENT"]) ? StringFus::strip_oneLineInput($_SERVER["HTTP_USER_AGENT"]) : '';
		
		return file_put_contents(Files::get_file_responses($study_id, Files::FILENAME_WEB_ACCESS), "\n\"".self::get_milliseconds()."\";\"$pageName\";\"$referer\";\"$user_agent\"", FILE_APPEND | LOCK_EX);
	}
	
	
	static function create_cookie($name, $value, $expires) {
		if(version_compare(phpversion(), '7.3', '<'))
			setcookie($name, $value, $expires);
		else
			setcookie($name, $value, ['expires' => $expires, 'sameSite' => 'Strict']);
	}
	static function delete_cookie($name) {
		self::create_cookie($name, '0', time() - 3600);
	}
	
	static function freeze_study($study_id, $lock=true) {
		$file = Files::get_file_lock($study_id);
		
		if(file_exists($file)) {
			if(!$lock)
				unlink($file);
		}
		else {
			if($lock)
				file_put_contents($file, '1');
		}
	}
	static function study_is_locked($study_id) {
		$file = Files::get_file_lock($study_id);
		
		return file_exists($file);
	}
	
	static function get_newMetadata($study) {
		return [
			'version' => (int)$study->version,
			'published' => isset($study->published) ? $study->published : false,
			'accessKeys' => $study->accessKeys,
			'lastBackup' => self::get_milliseconds()
		];
	}
	
	static function get_fresh_serverStatistics() {
		return [
			'days' => new stdClass(),
//		'questionnaire' => new stdClass(),
//		'join' => new stdClass(),
			'week' => [
				'questionnaire' => [0,0,0,0,0,0,0],
				'joined' => [0,0,0,0,0,0,0]
			],
			'total' => [
				'studies' => 0,
				'users' => 0,
				'android' => 0,
				'ios' => 0,
				'web' => 0,
				'questionnaire' => 0,
				'joined' => 0,
				'quit' => 0
			],
			'created' => time()
		];
	}
	static function update_serverStatistics($fu, $values = null) {
		$file_serverStatistics = Files::FILE_SERVER_STATISTICS;
		if(!file_exists($file_serverStatistics)) {
			file_put_contents($file_serverStatistics, json_encode(self::get_fresh_serverStatistics()), LOCK_EX);
			chmod($file_serverStatistics, 0666);
		}
		
		$handle = fopen($file_serverStatistics, 'r+');
		if(!$handle) {
			self::report("Could not open $file_serverStatistics. Server statistics were not updated!");
			return;
		}
		if(!flock($handle, LOCK_EX))
			self::report("Could not lock $file_serverStatistics. Data could be lost!");
		
		$statistics = json_decode(fread($handle, filesize($file_serverStatistics)));
		
		
		if($fu($statistics, $values))
			return;
		
		if(fseek($handle, 0) == -1)
			self::report("fseek() failed on $file_serverStatistics. Server statistics were not updated");
		else if(!ftruncate($handle, 0))
			self::report("ftruncate() failed on $file_serverStatistics. Server statistics were not updated");
		else if(!fwrite($handle, json_encode($statistics)))
			self::report("Could not write to $file_serverStatistics. Server statistics were not updated");
		fflush($handle);
		flock($handle, LOCK_UN);
		fclose($handle);
	}
}