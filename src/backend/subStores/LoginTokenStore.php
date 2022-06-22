<?php

namespace backend\subStores;

use backend\CriticalError;

interface LoginTokenStore {
	public function loginTokenExists(string $username, string $tokenId): bool;
	public function getLoginToken(string $username, string $tokenId): string;
	public function getLoginTokenList($username): array;
	
	/**
	 * @throws CriticalError
	 */
	public function saveLoginToken(string $username, string $tokenHash, string $tokenId);
	public function removeLoginToken(string $username, string $tokenId);
	public function clearAllLoginToken(string $username);
}