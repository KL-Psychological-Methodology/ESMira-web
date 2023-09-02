<?php

namespace backend\fileSystem\loader;


use backend\dataClasses\UserData;
use backend\exceptions\CriticalException;
use backend\Main;

class UserDataLoader {
	/**
	 * @throws CriticalException
	 */
	public static function import(string $data): UserData {
		if(unserialize($data) === false)
			throw new CriticalException("Could not serialize data: \n$data");
		
		return unserialize($data);
	}
	
	public static function export(UserData $userToken): string {
		return serialize($userToken);
	}
}