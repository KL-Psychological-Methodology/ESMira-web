<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;

interface FallbackTokenStore
{
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
}
