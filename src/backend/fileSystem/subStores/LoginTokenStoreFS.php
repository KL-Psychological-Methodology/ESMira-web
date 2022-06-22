<?php

namespace backend\fileSystem\subStores;

use backend\dataClasses\TokenInfo;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\Permission;
use backend\subStores\LoginTokenStore;

class LoginTokenStoreFS implements LoginTokenStore {
	public function loginTokenExists(string $username, string $tokenId): bool {
		return file_exists(PathsFS::fileToken($username, $tokenId));
	}
	public function getLoginToken(string $username, string $tokenId): string {
		return file_get_contents(PathsFS::fileToken($username, $tokenId));
	}
	public function getLoginTokenList($username): array {
		$pathToken = PathsFS::folderToken($username);
		$currentToken = Permission::getCurrentLoginTokenId();
		
		$obj = [];
		if(file_exists($pathToken)) {
			$h_folder = opendir($pathToken);
			while($file = readdir($h_folder)) {
				if($file[0] != '.')
					$obj[] = new TokenInfo($file, filemtime($pathToken.$file), $file == $currentToken);
			}
			closedir($h_folder);
		}
		return $obj;
	}
	
	public function saveLoginToken(string $username, string $tokenHash, string $tokenId) {
		$path = PathsFS::folderToken($username);
		if(!file_exists($path))
			FileSystemBasics::createFolder($path);
		
		file_put_contents(PathsFS::fileToken($username, $tokenId), $tokenHash, LOCK_EX);
	}
	public function removeLoginToken(string $username, string $tokenId) {
		$path = PathsFS::fileToken($username, $tokenId);
		if(file_exists($path))
			unlink($path);
	}
	public function clearAllLoginToken(string $username) {
		$path = PathsFS::folderToken($username);
		$handle = opendir($path);
		while($file = readdir($handle)) {
			if($file[0] != '.')
				unlink($path.$file);
		}
		closedir($handle);
	}
}