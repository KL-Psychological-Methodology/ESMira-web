<?php

namespace backend;

use backend\Base;

class Configs {
	private static $configs = null;
	
	static function get($key) {
		return self::getAll()[$key];
	}
	static function getAll() {
		if(self::$configs)
			return self::$configs;
		else if(file_exists(Files::FILE_CONFIG))
			return self::$configs = require Files::FILE_CONFIG;
		else if(file_exists(Files::FILE_DEFAULT_CONFIG))
			return require Files::FILE_DEFAULT_CONFIG;
		else
			return [];
	}
	static function getDefaultAll() {
		return require Files::FILE_DEFAULT_CONFIG;
	}
	
	static function get_serverName() {
		$lang = Base::get_lang('_');
		$serverName_array = self::get('serverName');
		return isset($serverName_array[$lang]) ? $serverName_array[$lang] : (isset($serverName_array['_']) ? $serverName_array['_'] : '');
	}
	
	static function reload() {
		//we dont need to load data now. It will automatically be loaded as soon as we ask for some data
		self::$configs = null;
	}
}