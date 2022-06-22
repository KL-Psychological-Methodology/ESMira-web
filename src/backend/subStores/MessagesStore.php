<?php

namespace backend\subStores;

use backend\CriticalError;

interface MessagesStore {
	public function getStudiesWithUnreadMessagesForPermission(): array;
	/**
	 * @throws CriticalError
	 */
	public function getMessagesList(int $studyId, string $userId): array;
	public function getParticipantsWithMessages(int $studyId): array;
	/**
	 * @throws CriticalError
	 */
	public function updateOrArchivePendingMessages(int $studyId, string $userId, callable $callback);
	
	/**
	 * @throws CriticalError
	 */
	public function setMessagesAsRead(int $studyId, string $userId, array $messageTimestamps);
	/**
	 * @throws CriticalError
	 */
	function sendMessage(int $studyId, string $userId, string $from, string $content): int;
	/**
	 * @throws CriticalError
	 */
	public function receiveMessage(int $studyId, string $userId, string $from, string $content): int;
	/**
	 * @throws CriticalError
	 */
	function deleteMessage(int $studyId, string $userId, int $sentTimestamp);
}