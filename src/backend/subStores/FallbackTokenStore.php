<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;

interface FallbackTokenStore
{
	const SETUP_TOKEN_LENGTH = 32;
	const SETUP_TOKEN_EXPIRY_TIME = 60 * 60 * 1000; // One Hour
	const TOKEN_LENGTH = 128;

	public function getInboundTokensInfoForUser(string $accountName): array;

	public function getInboundTokens(): array;

	public function issueSetupToken(string $accountName): string;

	public function issueInboundToken(string $setupToken, string $encodedUrl): string;

	public function deleteInboundToken(string $accountName, string $encodedUrl);

	public function deleteInboundTokensForUser(string $accountName);

	public function checkInboundToken(string $token): bool;

	public function getInboundTokenUrl(string $token): string | false;

	public function registerOutboundToken(string $encodedUrl, string $token);

	public function getOutboundTokenUrls(): array;

	public function getOutboundTokenEncodedUrls(): array;

	public function hasOutboundTokenUrl(string $encodedUrl): bool;

	public function setOutboundTokensList(array $encodedUrl);

	public function getOutboundTokenForUrl(string $encodedUrl): ?string;
}
