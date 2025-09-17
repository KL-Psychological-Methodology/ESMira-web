<?php

namespace backend\noJs;

use backend\Main;

class Lang {
	private static $langCode;
	private static $cache;
	private static $initialized = false;
	static function init() {
		self::$langCode = Main::getLang();
		if(self::$langCode == "en") {
			self::$cache = json_decode(file_get_contents(DIR_BASE .'locales/'.self::$langCode.'.json'));
		} else {
			$stringsEn = json_decode(file_get_contents(DIR_BASE .'locales/en.json'));
			$stringsLang = json_decode(file_get_contents(DIR_BASE .'locales/'.self::$langCode.'.json'));
			self::$cache = (object) array_merge((array) $stringsEn, (array) $stringsLang);	
		}
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