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

	public function issueInboundToken(string $setupToken, string $url): string;

	public function deleteInboundToken(string $accountName, string $url);

	public function checkInboundToken(string $token): bool;

	public function registerOutboundToken(string $url, string $token);

	public function getOutboundTokenUrls(): array;

	public function hasOutboundTokenUrl(string $url): bool;

	public function setOutboundTokensList(array $urls);

	public function getOutboundTokenForUrl(string $url): ?string;
}
