<?php

namespace backend\subStores;

use backend\CriticalError;

interface LoginTokenStore {
	public function loginTokenExists(string $accountName, string $tokenId): bool;
	public function getLoginToken(string $accountName, string $tokenId): string;
	public function getLoginTokenList($accountName): array;
	
	/**
	 * @throws CriticalError
	 */
	public function saveLoginToken(string $accountName, string $tokenHash, string $tokenId);
	public function removeLoginToken(string $accountName, string $tokenId);
	public function clearAllLoginToken(string $accountName);
}