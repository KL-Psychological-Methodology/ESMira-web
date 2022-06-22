<?php

namespace backend\subStores;

use backend\CriticalError;

interface UserStore {
	public function getLoginHistoryCsv(string $username): string;
	/**
	 * @throws CriticalError
	 */
	public function addToLoginHistoryEntry(string $username, array $data);
	
	
	public function getPermissions(string $username): array;
	/**
	 * @throws CriticalError
	 */
	public function addStudyPermission(string $username, int $studyId, string $permCode);
	/**
	 * @throws CriticalError
	 */
	public function removeStudyPermission(string $username, int $studyId, string $permCode);
	/**
	 * @throws CriticalError
	 */
	public function setAdminPermission(string $username, bool $isAdmin);
	
	/**
	 * @throws CriticalError
	 */
	public function createBlocking($username);
	public function removeBlocking(string $username);
	public function getUserBlockedTime(string $username): int;
	
	/**
	 * @throws CriticalError
	 */
	public function getUserList(): array;
	public function checkUserLogin(string $username, string $password): bool;
	public function doesUserExist($username): bool;
	/**
	 * @throws CriticalError
	 */
	public function setUser($username, $password);
	
	/**
	 * @throws CriticalError
	 */
	function changeUsername(string $oldUsername, string $newUsername);
	
	/**
	 * @throws CriticalError
	 */
	public function removeUser($username);
}