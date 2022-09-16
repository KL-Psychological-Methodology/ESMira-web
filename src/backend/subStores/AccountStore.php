<?php

namespace backend\subStores;

use backend\CriticalError;

interface AccountStore {
	public function getLoginHistoryCsv(string $accountName): string;
	/**
	 * @throws CriticalError
	 */
	public function addToLoginHistoryEntry(string $accountName, array $data);
	
	
	public function getPermissions(string $accountName): array;
	/**
	 * @throws CriticalError
	 */
	public function addStudyPermission(string $accountName, int $studyId, string $permCode);
	/**
	 * @throws CriticalError
	 */
	public function removeStudyPermission(string $accountName, int $studyId, string $permCode);
	/**
	 * @throws CriticalError
	 */
	public function setAdminPermission(string $accountName, bool $isAdmin);
	
	/**
	 * @throws CriticalError
	 */
	public function createBlocking($accountName);
	public function removeBlocking(string $accountName);
	public function getAccountBlockedTime(string $accountName): int;
	
	/**
	 * @throws CriticalError
	 */
	public function getAccountList(): array;
	public function checkAccountLogin(string $accountName, string $password): bool;
	public function doesAccountExist($accountName): bool;
	/**
	 * @throws CriticalError
	 */
	public function setAccount($accountName, $password);
	
	/**
	 * @throws CriticalError
	 */
	function changeAccountName(string $oldAccountName, string $newAccountName);
	
	/**
	 * @throws CriticalError
	 */
	public function removeAccount($accountName);
}