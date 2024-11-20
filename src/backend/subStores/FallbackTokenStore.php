<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;

interface FallbackTokenStore
{
	const TOKEN_LENGTH = 32;
	/**
	 * @throws CriticalException
	 */
	public function getInboundTokensInfoForUser(string $accountName): array;
	/**
	 * @throws CriticalException
	 */
	public function issueInboundToken(string $accountName, string $url): string;
	/**
	 * @throws CriticalException
	 */
	public function deleteInboundToken(string $accountName, string $url);

	public function registerOutboundToken(string $url, string $token);

	public function getOutboundTokenUrls(): array;

	public function setOutboundTokensList(array $urls);
}
