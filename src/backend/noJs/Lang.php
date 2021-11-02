<?php

namespace backend\noJs;

use backend\Base;

class Lang {
	private static $langCode;
	private static $cache;
	private static $initialized = false;
	static function init() {
		self::$langCode = Base::get_lang('en');
		self::$cache = json_decode(file_get_contents(DIR_BASE .'frontend/locales/'.self::$langCode.'.json'));
		self::$initialized = true;
	}
	
	static function get($key) {
		if(!self::$initialized)
			self::init();
		
		return isset(self::$cache->{$key}) ? self::$cache->{$key} : $key;
	}
	static function getCode() {
		if(!self::$initialized)
			self::init();
		return self::$langCode;
	}
}