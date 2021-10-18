<?php
require_once 'string_fu.php';

function get_milliseconds() {
	return function_exists('microtime') ? round(microtime(true) * 1000) : time() * 1000;
}

function report($msg) {
	$filename = time();
	$location = FOLDER_ERRORS.$filename.ERROR_FILE_EXTENSION;
	
	$num = 1;
	while(file_exists($location)) {
		$location = FOLDER_ERRORS.(++$filename).ERROR_FILE_EXTENSION;
		if(++$num > 100)
			return false;
	}
	
	return file_put_contents($location, $msg) && chmod($location, 0666);
}
function get_lang() {
	if(isset($_GET['lang'])) {
		$lang = $_GET['lang'];
		create_cookie('lang', $_GET['lang'], 32532447600);
	}
	else if(isset($_COOKIE['lang']))
		$lang = $_COOKIE['lang'];
	else if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	}
	else
		$lang = 'en';
	
	switch($lang) {
		case 'de':
		case 'en':
			break;
		default:
			$lang = 'en';
	}
	return $lang;
}

function get_serverSettings() {
	if(!defined('SERVER_SETTINGS')) {
		if(file_exists(FILE_SERVER_SETTINGS)) {
			require_once FILE_SERVER_SETTINGS;
			return SERVER_SETTINGS;
		}
		else {
			if(!defined('DEFAULT_SERVER_SETTINGS'))
				require_once 'php/default_server_settings.php';
			return DEFAULT_SERVER_SETTINGS;
		}
	}
	else
		return SERVER_SETTINGS;
}

function get_serverName() {
	$serverSettings = get_serverSettings();
	$lang = get_lang();
	$serverName_array = $serverSettings['serverName'];
	return isset($serverName_array[$lang]) ? $serverName_array[$lang] : $serverName_array['_'];
}

function get_accessKey() {
	if(isset($_GET['key']) && check_input($_GET['key'])) {
		$key = strtolower(trim($_GET['key']));
		create_cookie('access_key', $key, 32532447600);
		if(strlen($key))
			return $key;
	}
	else if(isset($_COOKIE['access_key']) && check_input($_COOKIE['access_key'])) {
		$key = strtolower($_COOKIE['access_key']);
		if(strlen($key))
			return $key;
	}
	
	return false;
}

function save_webAccess($study_id, $pageName) {

//	$referer = isset($_SERVER["HTTP_REFERER"]) ? strip_input($_SERVER["HTTP_REFERER"]) : '';
	$referer = isset($_SERVER["HTTP_REFERER"]) ? strip_oneLineInput(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST)) : '';
	$user_agent = isset($_SERVER["HTTP_USER_AGENT"]) ? strip_oneLineInput($_SERVER["HTTP_USER_AGENT"]) : '';
	
	return file_put_contents(get_file_responses($study_id, FILENAME_WEB_ACCESS), "\n\"".get_milliseconds()."\";\"$pageName\";\"$referer\";\"$user_agent\"", FILE_APPEND | LOCK_EX);
}


function create_cookie($name, $value, $expires) {
	if(version_compare(phpversion(), '7.3', '<'))
		setcookie($name, $value, $expires);
	else
		setcookie($name, $value, ['expires' => $expires, 'sameSite' => 'Strict']);
}
function delete_cookie($name) {
	create_cookie($name, '0', time() - 3600);
}

function freeze_study($study_id, $lock=true) {
	$file = get_file_lock($study_id);
	
	if(file_exists($file)) {
		if(!$lock)
			unlink($file);
	}
	else {
		if($lock)
			file_put_contents($file, '1');
	}
}
function study_is_locked($study_id) {
	$file = get_file_lock($study_id);
	
	return file_exists($file);
}

function get_newMetadata($study) {
	return [
		'version' => (int)$study->version,
		'published' => isset($study->published) ? $study->published : false,
		'accessKeys' => $study->accessKeys,
		'lastBackup' => get_milliseconds()
	];
}

function get_fresh_serverStatistics() {
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
function update_serverStatistics($fu, $values = null) {
	$file_serverStatistics = FILE_SERVER_STATISTICS;
	if(!file_exists($file_serverStatistics)) {
		file_put_contents($file_serverStatistics, json_encode(get_fresh_serverStatistics()), LOCK_EX);
		chmod($file_serverStatistics, 0666);
	}
	
	$handle = fopen($file_serverStatistics, 'r+');
	if(!$handle) {
		report("Could not open $file_serverStatistics. Server statistics were not updated!");
		return;
	}
	if(!flock($handle, LOCK_EX))
		report("Could not lock $file_serverStatistics. Data could be lost!");
	
	$statistics = json_decode(fread($handle, filesize($file_serverStatistics)));
	
	
	if($fu($statistics, $values))
		return;
	
	if(fseek($handle, 0) == -1)
		report("fseek() failed on $file_serverStatistics. Server statistics were not updated");
	else if(!ftruncate($handle, 0))
		report("ftruncate() failed on $file_serverStatistics. Server statistics were not updated");
	else if(!fwrite($handle, json_encode($statistics)))
		report("Could not write to $file_serverStatistics. Server statistics were not updated");
	fflush($handle);
	flock($handle, LOCK_UN);
	fclose($handle);
}

?>