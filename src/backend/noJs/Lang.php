<?php

namespace backend\noJs;

use backend\Main;

class Lang {
	private static $langCode;
	private static $cache;
	private static $initialized = false;
	static function init() {
		self::$langCode = Main::getLang();
		self::$cache = json_decode(file_get_contents(DIR_BASE .'locales/'.self::$langCode.'.json'));
		self::$initialized = true;
	}
	
	static function get($key, ... $arguments): string {
		if(!self::$initialized)
			self::init();
		
		$value = self::$cache->{$key} ?? $key;
		if($arguments)
			$value = call_user_func_array('sprintf', array_merge([$value], $arguments));
		return $value;
	}
}