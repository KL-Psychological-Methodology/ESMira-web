<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;

interface AccountStore {
	public function getLoginHistoryCsv(string $accountName): string;
	/**
	 * @throws \backend\exceptions\CriticalException
	 */
	public function addToLoginHistoryEntry(string $accountName, array $data);
	
	
	public function getPermissions(string $accountName): array;
	/**
	 * @throws \backend\exceptions\CriticalException
	 */
	public function addStudyPermission(string $accountName, int $studyId, string $permCode);
	/**
	 * @throws CriticalException
	 */
	public function removeStudyPermission(string $accountName, int $studyId, string $permCode);
	/**
	 * @throws \backend\exceptions\CriticalException
	 */
	public function setAdminPermission(string $accountName, bool $isAdmin);
	
	/**
	 * @throws \backend\exceptions\CriticalException
	 */
	public function createBlocking($accountName);
	public function removeBlocking(string $accountName);
	public function getAccountBlockedTime(string $accountName): int;
	
	/**
	 * @throws CriticalException
	 */
	public function getAccountList(): array;
	public function checkAccountLogin(string $accountName, string $password): bool;
	public function doesAccountExist($accountName): bool;
	/**
	 * @throws CriticalException
	 */
	public function setAccount($accountName, $password);
	
	/**
	 * @throws CriticalException
	 */
	function changeAccountName(string $oldAccountName, string $newAccountName);
	
	/**
	 * @throws CriticalException
	 */
	public function removeAccount($accountName);
}