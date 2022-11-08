<?php

namespace backend;

use backend\exceptions\CriticalException;

const IS_TEST_INSTANCE = PHP_SAPI == 'cli';

class Main {
	const SERVER_VERSION = 11,
		ACCEPTED_SERVER_VERSION = 7;
	public static $defaultPostInput = ''; //only used for testing
	
	/**
	 * @throws CriticalException
	 */
	static function sessionStart() {
		switch(session_status()) {
			case PHP_SESSION_ACTIVE:
				break;
			case PHP_SESSION_NONE:
				if(!IS_TEST_INSTANCE)
					session_start();
				break;
			case PHP_SESSION_DISABLED:
			default:
				throw new CriticalException('This server does not support sessions!');
		}
	}
	
	static function setHeader($header) {
		if(IS_TEST_INSTANCE)
			return;
		header($header);
	}
	
	static function getMilliseconds(): int {
		return function_exists('microtime') ? round(microtime(true) * 1000) : time() * 1000;
	}
	
	static function report(string $msg): bool {
		return Configs::getDataStore()->getErrorReportStore()->saveErrorReport($msg);
	}
	static function getLang(string $default = '_'): string {
		if(isset($_GET['lang'])) {
			$lang = $_GET['lang'];
			self::setCookie('lang', $_GET['lang'], 32532447600);
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
	
	static function getRawPostInput(): string {
		return IS_TEST_INSTANCE ? self::$defaultPostInput : file_get_contents('php://input');
	}
	
	static function getAccessKey(): string {
		if(isset($_GET['key']) && self::strictCheckInput($_GET['key'])) {
			$key = strtolower(trim($_GET['key']));
			self::setCookie('access_key', $key);
			if(strlen($key))
				return $key;
		}
		else if(isset($_COOKIE['access_key']) && self::strictCheckInput($_COOKIE['access_key'])) {
			$key = strtolower($_COOKIE['access_key']);
			if(strlen($key))
				return $key;
		}
		
		return '';
	}
	
	static function setCookie(string $key, string $value, int $expires = 32532447600) {
		$_COOKIE[$key] = $value;
		if(IS_TEST_INSTANCE)
			return;
		if(version_compare(phpversion(), '7.3', '<'))
			setcookie($key, $value, $expires);
		else
			setcookie($key, $value, ['expires' => $expires, 'sameSite' => 'Strict']);
	}
	static function deleteCookie($name) {
		self::setCookie($name, '', 1);
		unset($_COOKIE[$name]);
	}
	
	public static function strictCheckInput($s): bool {
		return empty($s) || (strlen($s) < Configs::get('user_input_max_length') && preg_match('/^[a-zA-Z0-9À-ž_\-().\s]+$/', $s));
	}
	
	static function arrayToCSV(array $data, string $csvDelimiter): string {
		return '"' .implode('"' .$csvDelimiter .'"', $data) .'"';
	}
}