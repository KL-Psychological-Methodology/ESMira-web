<?php

namespace backend\fileSystem\subStores;

use backend\dataClasses\InboundFallbackToken;
use backend\exceptions\CriticalException;
use backend\fileSystem\loader\FallbackTokenLoader;
use backend\Permission;
use backend\PermissionTokenType;
use backend\subStores\FallbackTokenStore;

class FallbackTokenStoreFS implements FallbackTokenStore
{
	private function checkIsUniqueUrl(string $accountName, string $url): bool
	{
		$inboundTokens = FallbackTokenLoader::importInboundFile();
		if (!isset($inboundTokens[$accountName]))
			return true;
		foreach ($inboundTokens[$accountName] as $token) {
			if (!$token instanceof InboundFallbackToken)
				throw new CriticalException("Invalid data in FallbackTokenStore");
			if ($url == $token->otherServerUrl)
				return false;
		}
		return true;
	}

	public function getInboundTokensInfoForUser(string $accountName): array
	{
		$inboundTokens = FallbackTokenLoader::importInboundFile();

		if (!isset($inboundTokens[$accountName]))
			return [];
		$inboundTokensInfo = [];
		foreach ($inboundTokens[$accountName] as $tokenInfo) {
			if (!$tokenInfo instanceof InboundFallbackToken) {
				throw new CriticalException("Invalid data in FallbackTokenStore");
			}
			$inboundTokensInfo[] = [
				"user" => $accountName,
				"otherServerUrl" => $tokenInfo->otherServerUrl
			];
		}
		return $inboundTokensInfo;
	}

	public function issueInboundToken(string $accountName, string $url): string
	{
		if (!$this->checkIsUniqueUrl($accountName, $url))
			throw new CriticalException("Inbound Fallback Token url must be unique per user");
		$token = Permission::calcRandomToken(self::TOKEN_LENGTH, PermissionTokenType::BASE64);
		$hashedToken = Permission::getHashedToken($token);
		$tokenInfo = new InboundFallbackToken($url, $hashedToken);

		$inboundTokens = FallbackTokenLoader::importInboundFile();
		if (!isset($inboundTokens[$accountName]))
			$inboundTokens[$accountName] = [];
		$inboundTokens[$accountName][] = $tokenInfo;
		FallbackTokenLoader::exportInboundFile($inboundTokens);

		return $token;
	}

	public function deleteInboundToken(string $accountName, string $url)
	{
		$inboundTokens = FallbackTokenLoader::importInboundFile();
		if (!isset($inboundTokens[$accountName]))
			return;
		$trimmedAccountTokens = [];
		$accountTokens = $inboundTokens[$accountName];
		$didRemove = false;
		foreach ($accountTokens as $token) {
			if (! $token instanceof InboundFallbackToken) {
				throw new CriticalException("Invalid data in FallbackTokenStore");
			}
			if (strcmp($url, $token->otherServerUrl) === 0) {
				$didRemove = true;
			} else {
				$trimmedAccountTokens[] = $token;
			}
		}
		if (!$didRemove) {
			return;
		}
		$inboundTokens[$accountName] = $trimmedAccountTokens;
		FallbackTokenLoader::exportInboundFile($inboundTokens);
	}
}