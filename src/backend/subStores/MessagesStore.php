<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;

interface MessagesStore {
	public function getStudiesWithUnreadMessagesForPermission(): array;
	/**
	 * @throws CriticalException
	 */
	public function getMessagesList(int $studyId, string $userId): array;
	public function getParticipantsWithMessages(int $studyId): array;
	/**
	 * @throws CriticalException
	 */
	public function updateOrArchivePendingMessages(int $studyId, string $userId, callable $callback);
	
	/**
	 * @throws CriticalException
	 */
	public function setMessagesAsRead(int $studyId, string $userId, array $messageTimestamps);
	/**
	 * @throws CriticalException
	 */
	function sendMessage(int $studyId, string $userId, string $from, string $content): int;
	/**
	 * @throws CriticalException
	 */
	public function receiveMessage(int $studyId, string $userId, string $from, string $content): int;
	/**
	 * @throws CriticalException
	 */
	function deleteMessage(int $studyId, string $userId, int $sentTimestamp);
}