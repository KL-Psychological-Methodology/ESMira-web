<?php

namespace backend\fileSystem\subStores;

use backend\dataClasses\TokenInfo;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\Permission;
use backend\subStores\LoginTokenStore;

class LoginTokenStoreFS implements LoginTokenStore {
	public function loginTokenExists(string $accountName, string $tokenId): bool {
		return file_exists(PathsFS::fileToken($accountName, $tokenId));
	}
	public function getLoginToken(string $accountName, string $tokenId): string {
		return file_get_contents(PathsFS::fileToken($accountName, $tokenId));
	}
	public function getLoginTokenList($accountName): array {
		$pathToken = PathsFS::folderToken($accountName);
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
	
	public function saveLoginToken(string $accountName, string $tokenHash, string $tokenId) {
		$path = PathsFS::folderToken($accountName);
		if(!file_exists($path))
			FileSystemBasics::createFolder($path);
		
		file_put_contents(PathsFS::fileToken($accountName, $tokenId), $tokenHash, LOCK_EX);
	}
	public function removeLoginToken(string $accountName, string $tokenId) {
		$path = PathsFS::fileToken($accountName, $tokenId);
		if(file_exists($path))
			unlink($path);
	}
	public function clearAllLoginToken(string $accountName) {
		$path = PathsFS::folderToken($accountName);
		$handle = opendir($path);
		while($file = readdir($handle)) {
			if($file[0] != '.')
				unlink($path.$file);
		}
		closedir($handle);
	}
}