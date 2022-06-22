<?php
declare(strict_types=1);

namespace backend;


class Configs {
	static $configs = null;
	private static $dataStore = null;
	
	static function getDataStore(): DataStoreInterface {
		if(!self::$dataStore) {
			$className = self::get('dataStore');
			self::$dataStore = new $className();
		}
		
		return self::$dataStore;
	}
	
	static function get(string $key) {
		$current = self::getAll();
		if(isset($current[$key]))
			return $current[$key];
		else {
			$default = self::getDefaultAll();
			return $default[$key] ?? null;
		}
	}
	static function getAll(): array {
		if(self::$configs)
			return self::$configs;
		
		return self::$configs = file_exists(Paths::FILE_CONFIG) ? require Paths::FILE_CONFIG : self::getDefaultAll();
	}
	static function getDefaultAll(): array {
		return file_exists(Paths::FILE_DEFAULT_CONFIG) ? require Paths::FILE_DEFAULT_CONFIG : [];
	}
	
	static function getServerName(): string {
		$lang = Main::getLang();
		$serverNameArray = self::get('serverName');
		return $serverNameArray[$lang] ?? ($serverNameArray['_'] ?? '');
	}
	
	static function resetConfig() {
		//we dont need to load data now. It will automatically be loaded as soon as we ask for some data
		self::$configs = null;
	}
	static function resetAll() {
		self::resetConfig();
		self::$dataStore = null;
	}
	
	static function injectConfig($path) { //for testing
		self::$configs = require DIR_BASE ."test/testConfigs/$path";
	}
	static function injectDataStore(DataStoreInterface $dataStore) { //for testing
		self::$dataStore = $dataStore;
	}
}