<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;

interface LoginTokenStore {
	public function loginTokenExists(string $accountName, string $tokenId): bool;
	public function getLoginToken(string $accountName, string $tokenId): string;
	public function getLoginTokenList($accountName): array;
	
	/**
	 * @throws CriticalException
	 */
	public function saveLoginToken(string $accountName, string $tokenHash, string $tokenId);
	public function removeLoginToken(string $accountName, string $tokenId);
	public function clearAllLoginToken(string $accountName);
}