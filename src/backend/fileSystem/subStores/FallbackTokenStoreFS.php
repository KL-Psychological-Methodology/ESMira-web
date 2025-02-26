<?php

namespace backend\fileSystem\subStores;

use backend\Configs;
use backend\dataClasses\FallbackSetupToken;
use backend\dataClasses\InboundFallbackToken;
use backend\dataClasses\OutboundFallbackToken;
use backend\exceptions\CriticalException;
use backend\exceptions\FallbackRequestException;
use backend\FallbackRequest;
use backend\fileSystem\loader\FallbackTokenLoader;
use backend\Main;
use backend\Permission;
use backend\PermissionTokenType;
use backend\subStores\FallbackTokenStore;

class FallbackTokenStoreFS implements FallbackTokenStore
{
	private function checkIsUniqueUrl(string $encodedUrl): bool
	{
		$inboundTokens = FallbackTokenLoader::importInboundFile();
		foreach ($inboundTokens as $token) {
			if (!$token instanceof InboundFallbackToken)
				throw new CriticalException("Invalid data in FallbackTokenStore");
			if (strcmp($token->otherServerUrl, $encodedUrl) === 0)
				return false;
		}
		return true;
	}

	private function getValidSetupTokens(): array
	{
		$rawSetupTokens = FallbackTokenLoader::importSetupFile();
		$now = Main::getMilliseconds();
		$setupTokens = [];
		foreach ($rawSetupTokens as $token) {
			if (!$token instanceof FallbackSetupToken)
				throw new CriticalException("Invalid data in FallbackTokenStore");
			if ($token->creationTime + self::SETUP_TOKEN_EXPIRY_TIME >= $now) {
				$setupTokens[] = $token;
			}
		}
		FallbackTokenLoader::exportSetupFile($setupTokens);
		return $setupTokens;
	}

	public function getInboundTokensInfoForUser(string $accountName): array
	{
		$inboundTokens = FallbackTokenLoader::importInboundFile();

		$inboundTokensInfo = [];

		foreach ($inboundTokens as $tokenInfo) {
			if (!$tokenInfo instanceof InboundFallbackToken)
				throw new CriticalException("Invalid data in FallbackTokenStore");
			if (strcmp($tokenInfo->issuingUser, $accountName) === 0) {
				$inboundTokensInfo[] = [
					"user" => $accountName,
					"otherServerUrl" => $tokenInfo->otherServerUrl
				];
			}
		}
		return $inboundTokensInfo;
	}

	public function getInboundTokens(): array
	{
		$inboundTokens = FallbackTokenLoader::importInboundFile();

		$inboundTokensInfo = [];

		foreach ($inboundTokens as $tokenInfo) {
			if (!$tokenInfo instanceof InboundFallbackToken)
				throw new CriticalException("Invalid data in FallbackTokenStore");
			$inboundTokensInfo[] = [
				"user" => $tokenInfo->issuingUser,
				"otherServerUrl" => $tokenInfo->otherServerUrl
			];
		}
		return $inboundTokensInfo;
	}

	public function issueSetupToken(string $accountName): string
	{
		$token = Permission::calcRandomToken(self::SETUP_TOKEN_LENGTH, PermissionTokenType::BASE64);
		$hashedToken = Permission::getHashedToken($token);
		$now = Main::getMilliseconds();
		$tokenInfo = new FallbackSetupToken($hashedToken, $accountName, $now);

		$setupTokens = $this->getValidSetupTokens();
		$setupTokens[] = $tokenInfo;
		FallbackTokenLoader::exportSetupFile($setupTokens);

		return $token;
	}

	private function consumeSetupToken(string $token): ?FallbackSetupToken
	{
		$setupTokens = $this->getValidSetupTokens();
		$hashedToken = Permission::getHashedToken($token);

		$trimmedSetupTokens = [];
		$selectedSetupToken = null;

		foreach ($setupTokens as $setupToken) {
			if (strcmp($setupToken->hashedToken, $hashedToken) === 0) {
				$selectedSetupToken = $setupToken;
			} else {
				$trimmedSetupTokens[] = $setupToken;
			}
		}

		if ($selectedSetupToken !== null) {
			FallbackTokenLoader::exportSetupFile($trimmedSetupTokens);
		}
		return $selectedSetupToken;
	}

	public function issueInboundToken(string $setupToken, string $encodedUrl): string
	{
		if (!$this->checkIsUniqueUrl($encodedUrl))
			throw new CriticalException("An Inbound Fallback Token already exists for the given URL.");

		$setupTokenInfo = $this->consumeSetupToken($setupToken);
		if ($setupTokenInfo == null)
			throw new CriticalException("Provided Token does not match any Setup Tokens found on Server.");

		$token = Permission::calcRandomToken(self::TOKEN_LENGTH, PermissionTokenType::BASE64);
		$hashedToken = Permission::getHashedToken($token);
		$inboundTokenInfo = new InboundFallbackToken($encodedUrl, $hashedToken, $setupTokenInfo->issuingUser);

		$inboundTokens = FallbackTokenLoader::importInboundFile();
		$inboundTokens[] = $inboundTokenInfo;
		FallbackTokenLoader::exportInboundFile($inboundTokens);

		return $token;
	}

	public function deleteInboundToken(string $accountName, string $encodedUrl)
	{
		$inboundTokens = FallbackTokenLoader::importInboundFile();
		$trimmedTokens = [];
		$didRemove = false;
		foreach ($inboundTokens as $token) {
			if (! $token instanceof InboundFallbackToken) {
				throw new CriticalException("Invalid data in FallbackTokenStore");
			}
			if (strcmp($encodedUrl, $token->otherServerUrl) === 0) {
				if (strcmp($accountName, $token->issuingUser) !== 0) {
					throw new CriticalException("URL and issuing user do not match.");
				}
				$didRemove = true;
			} else {
				$trimmedTokens[] = $token;
			}
		}
		if (!$didRemove) {
			return;
		}
		FallbackTokenLoader::exportInboundFile($trimmedTokens);

		Configs::getDataStore()->getFallbackStudyStore($encodedUrl)->deleteStore();
	}

	public function deleteInboundTokensForUser(string $accountName)
	{
		$tokens = $this->getInboundTokensInfoForUser($accountName);
		foreach ($tokens as $token) {
			$this->deleteInboundToken($accountName, $token->otherServerUrl);
		}
	}

	public function checkInboundToken(string $token): bool
	{
		$hashedToken = Permission::getHashedToken($token);
		$inboundTokens = FallbackTokenLoader::importInboundFile();
		foreach ($inboundTokens as $token) {
			if (!$token instanceof InboundFallbackToken) {
				throw new CriticalException("Invalid data in FallbackTokenStore");
			}
			if (strcmp($hashedToken, $token->hashedToken) === 0)
				return true;
		}
		return false;
	}

	public function getInboundTokenUrl(string $token): string
	{
		$hashedToken = Permission::getHashedToken($token);
		$inboundTokens = FallbackTokenLoader::importInboundFile();
		foreach ($inboundTokens as $token) {
			if (!$token instanceof InboundFallbackToken) {
				throw new CriticalException("Invalid data in FallbackTokenStore");
			}
			if (strcmp($hashedToken, $token->hashedToken) === 0)
				return $token->otherServerUrl;
		}
		return "";
	}

	public function registerOutboundToken(string $encodedUrl, string $newToken)
	{
		$outboundTokens = FallbackTokenLoader::importOutboundFile();
		foreach ($outboundTokens as $token) {
			if (!$token instanceof OutboundFallbackToken) {
				throw new CriticalException("Invalid data in FallbackTokenStore");
			}
			if (strcmp($encodedUrl, $token->url) === 0) {
				throw new CriticalException("Outbound Fallback Token URLs must be unique");
			}
		}
		$outboundTokens[] = new OutboundFallbackToken($newToken, $encodedUrl);
		FallbackTokenLoader::exportOutboundFile($outboundTokens);
	}

	public function deleteOutboundToken(string $encodedUrl)
	{
		$outboundTokens = FallbackTokenLoader::importOutboundFile();
		$trimmedOutboundTokens = [];
		$didRemove = false;
		foreach ($outboundTokens as $token) {
			if (!$token instanceof OutboundFallbackToken)
				throw new CriticalException("Invalid data in FallbackTokenStore");
			if (strcmp($encodedUrl, $token->url) === 0) {
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
			$urlList[] = ['url' => base64_decode($token->url)];
		}
		return $urlList;
	}

	public function getOutboundTokenEncodedUrls(): array
	{
		$outboundTokens = FallbackTokenLoader::importOutboundFile();
		$urlList = [];
		foreach ($outboundTokens as $token) {
			if (!$token instanceof OutboundFallbackToken)
				throw new CriticalException("Invalid data in FallbackTokenStore");
			$urlList[] = $token->url;
		}
		return $urlList;
	}

	public function hasOutboundTokenUrl(string $encodedUrl): bool
	{
		$outboundTokens = FallbackTokenLoader::importOutboundFile();
		foreach ($outboundTokens as $token) {
			if (!$token instanceof OutboundFallbackToken)
				throw new CriticalException("Invalid data in FallbackTokenStore");
			if (strcmp($encodedUrl, $token->url) === 0)
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

	public function getOutboundTokenForUrl(string $encodedUrl): ?string
	{
		$outboundTokens = FallbackTokenLoader::importOutboundFile();
		foreach ($outboundTokens as $token) {
			if (!$token instanceof OutboundFallbackToken)
				throw new CriticalException("Invalid data in FallbackTokenStore");
			if (strcmp($encodedUrl, $token->url) === 0) {
				return $token->token;
			}
		}
		return null;
	}
}
