<?php

namespace backend\fileSystem\loader;


use backend\dataClasses\UserData;

class UserDataLoader {
	public static function import(string $data): UserData {
		return unserialize($data);
	}
	
	public static function export(UserData $userToken): string {
		return serialize($userToken);
	}
}