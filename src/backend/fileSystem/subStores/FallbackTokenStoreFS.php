<?php

namespace backend\fileSystem\subStores;

use backend\dataClasses\InboundFallbackToken;
use backend\dataClasses\OutboundFallbackToken;
use backend\exceptions\CriticalException;
use backend\exceptions\FallbackFeatureException;
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

	public function checkInboundToken(string $token): bool
	{
		$hashedToken = Permission::getHashedToken($token);
		$inboundTokens = FallbackTokenLoader::importInboundFile();
		foreach ($inboundTokens as $accountTokens) {
			foreach ($accountTokens as $token) {
				if (!$token instanceof InboundFallbackToken) {
					throw new CriticalException("Invalid data in FallbackTokenStore");
				}
				if (strcmp($hashedToken, $token->hashedToken) === 0)
					return true;
			}
		}
		return false;
	}

	public function registerOutboundToken(string $url, string $newToken)
	{
		$outboundTokens = FallbackTokenLoader::importOutboundFile();
		foreach ($outboundTokens as $token) {
			if (!$token instanceof OutboundFallbackToken) {
				throw new CriticalException("Invalid data in FallbackTokenStore");
			}
			if (strcmp($url, $token->url) === 0) {
				throw new CriticalException("Outbound Fallback Token URLs must be unique");
			}
		}
		$outboundTokens[] = new OutboundFallbackToken($newToken, $url);
		FallbackTokenLoader::exportOutboundFile($outboundTokens);
	}

	public function deleteOutboundToken(string $url)
	{
		$outboundTokens = FallbackTokenLoader::importOutboundFile();
		$trimmedOutboundTokens = [];
		$didRemove = false;
		foreach ($outboundTokens as $token) {
			if (!$token instanceof OutboundFallbackToken)
				throw new CriticalException("Invalid data in FallbackTokenStore");
			if (strcmp($url, $token->url) === 0) {
				$didRemove = true;
			} else {
				$trimmedOutboundTokens[] = $token;
			}
		}
		if (!$didRemove)
			return;
		FallbackTokenLoader::exportOutboundFile($trimmedOutboundTokens);
	}

	public function getOutboundTokenUrls(): array
	{
		$outboundTokens = FallbackTokenLoader::importOutboundFile();
		$urlList = [];
		foreach ($outboundTokens as $token) {
			if (!$token instanceof OutboundFallbackToken)
				throw new CriticalException("Invalid data in FallbackTokenStore");
			$urlList[] = ['url' => $token->url];
		}
		return $urlList;
	}

	public function hasOutboundTokenUrl(string $url): bool
	{
		$outboundTokens = FallbackTokenLoader::importOutboundFile();
		foreach ($outboundTokens as $token) {
			if (!$token instanceof OutboundFallbackToken)
				throw new CriticalException("Invalid data in FallbackTokenStore");
			if (strcmp($url, $token->url) === 0)
				return true;
		}
		return false;
	}

	public function setOutboundTokensList(array $urls)
	{
		$outboundTokens = FallbackTokenLoader::importOutboundFile();
		$urlMap = [];
		foreach ($outboundTokens as $token) {
			if (!$token instanceof OutboundFallbackToken)
				throw new CriticalException("Invalid data in FallbackTokenStore");
			$urlMap[$token->url] = $token;
		}
		$newOutboundTokens = [];
		foreach ($urls as $url) {
			if (!isset($urlMap[$url])) {
				throw new CriticalException("Nonexistant URL in input list");
			}
			$newOutboundTokens[] = $urlMap[$url];
		}
		FallbackTokenLoader::exportOutboundFile($newOutboundTokens);
	}

	public function getOutboundTokenForUrl(string $url): string
	{
		$outboundTokens = FallbackTokenLoader::importOutboundFile();
		foreach ($outboundTokens as $token) {
			if (!$token instanceof OutboundFallbackToken)
				throw new CriticalException("Invalid data in FallbackTokenStore");
			if (strcmp($url, $token->url) === 0) {
				return $token->token;
			}
		}
		throw new CriticalException("No Fallback Token exists for given URL");
	}
}