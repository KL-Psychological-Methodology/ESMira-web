<?php

namespace backend\fileSystem\subStores;

use backend\Configs;
use backend\CriticalError;
use backend\dataClasses\Message;
use backend\dataClasses\MessageParticipantInfo;
use backend\dataClasses\MessagesList;
use backend\Paths;
use backend\fileSystem\PathsFS;
use backend\fileSystem\loader\MessagesArchivedLoader;
use backend\fileSystem\loader\MessagesPendingLoader;
use backend\fileSystem\loader\MessagesUnreadLoader;
use backend\Permission;
use backend\subStores\MessagesStore;

class MessagesStoreFS implements MessagesStore {
	private function hasMessages(int $studyId): bool {
		$path = PathsFS::folderMessagesUnread($studyId);
		if(!file_exists($path))
			return false;
		$handle = opendir($path);
		while($file = readdir($handle)) {
			if($file[0] != '.') {
				closedir($handle);
				return true;
			}
		}
		closedir($handle);
		return false;
	}
	public function getStudiesWithUnreadMessagesForPermission(): array {
		$permissions = Permission::getPermissions();
		$isAdmin = $permissions['admin'] ?? false;
		$msgPermissions = $permissions['msg'] ?? [];
		$ids = [];
		$handle = opendir(PathsFS::folderStudies());
		while($studyId = readdir($handle)) {
			if($studyId[0] === '.' || $studyId === PathsFS::FILENAME_STUDY_INDEX)
				continue;
			
			if(($isAdmin || in_array($studyId, $msgPermissions)) && $this->hasMessages($studyId)) {
				$ids[] = $studyId;
			}
		}
		closedir($handle);
		return $ids;
	}
	public function getMessagesList(int $studyId, string $userId): array {
		if(empty($userId))
			return MessagesList::get([], [], []);
		return MessagesList::get(
			MessagesArchivedLoader::importFile($studyId, $userId),
			MessagesPendingLoader::importFile($studyId, $userId),
			MessagesUnreadLoader::importFile($studyId, $userId)
		);
	}
	public function getParticipantsWithMessages(int $studyId): array {
		$list = [];
		$index = [];
		
		$source = [
			'archived' => PathsFS::folderMessagesArchive($studyId),
			'pending' => PathsFS::folderMessagesPending($studyId),
			'unread' => PathsFS::folderMessagesUnread($studyId)
		];
		foreach($source as $attr => $path) {
			if(!file_exists($path))
				continue;
			
			$handle = opendir($path);
			while($file = readdir($handle)) {
				if($file[0] == '.')
					continue;
				
				$username = Paths::getFromUrlFriendly($file);
				if(!isset($index[$username])) {
					$msgInfo = new MessageParticipantInfo($username, filemtime($path .$file) * 1000);
					
					$list[] = $msgInfo;
					$index[$username] = $msgInfo;
				}
				else
					$msgInfo = $index[$username];
				
				if($attr)
					$msgInfo->{$attr} = true;
			}
			closedir($handle);
		}
		
		return $list;
	}
	
	public function updateOrArchivePendingMessages(int $studyId, string $userId, callable $callback) {
		$pendingMessages = MessagesPendingLoader::importFile($studyId, $userId, true);
		$newPendingMessages = [];
		$newArchivedMessages = [];
		
		foreach($pendingMessages as $message) {
			$updateMessage = $callback($message);
			
			if($updateMessage)
				$newPendingMessages[] = $message;
			else {
				$message->pending = false;
				$message->archived = true;
				$newArchivedMessages[] = $message;
			}
		}
		if(empty($newPendingMessages)) {
			$path = PathsFS::fileMessagePending($studyId, $userId);
			if(!unlink($path))
				throw new CriticalError('Could not update messages');
		}
		else
			MessagesPendingLoader::exportFile($studyId, $userId, $newPendingMessages);
		
		if(!empty($newArchivedMessages)) {
			$archivedMessages = array_merge(
				MessagesArchivedLoader::importFile($studyId, $userId, true),
				$newArchivedMessages
			);
			
			$maxMessages = Configs::get('max_msgs_per_user');
			if(count($archivedMessages) > $maxMessages)
				$archivedMessages = array_slice($archivedMessages, count($archivedMessages) - $maxMessages, $maxMessages);
			
			MessagesArchivedLoader::exportFile($studyId, $userId, $archivedMessages);
		}
	}
	public function setMessagesAsRead(int $studyId, string $userId, array $messageTimestamps) {
		$pathUnread = PathsFS::fileMessageUnread($studyId, $userId);
		if(!file_exists($pathUnread))
			return;
		
		try {
			$messagesArchived = MessagesArchivedLoader::importFile($studyId, $userId, true);
			$messagesUnread = MessagesUnreadLoader::importFile($studyId, $userId, true);
			$newMessagesUnread = [];
			
			foreach($messagesUnread as $msg) {
				if(in_array($msg->sent, $messageTimestamps)) {
					$msg->unread = false;
					$msg->archived = true;
					$messagesArchived[] = $msg;
				}
				else
					$newMessagesUnread[] = $msg;
			}
			
			MessagesArchivedLoader::exportFile($studyId, $userId, $messagesArchived);
			MessagesUnreadLoader::exportFile($studyId, $userId, $newMessagesUnread);
		}
		finally {
			MessagesArchivedLoader::close();
			MessagesUnreadLoader::close();
		}
	}
	public function sendMessage(int $studyId, string $userId, string $from, string $content): int {
		$messages = MessagesPendingLoader::importFile($studyId, $userId);
		$msg = new Message($from, $content, true);
		$messages[] = $msg;
		MessagesPendingLoader::exportFile($studyId, $userId, $messages);
		return $msg->sent;
	}
	
	/**
	 * @throws CriticalError
	 */
	public function receiveMessage(int $studyId, string $userId, string $from, string $content): int {
		$messages = MessagesUnreadLoader::importFile($studyId, $userId, true);
		$msg = new Message($from, $content, false, true);
		$messages[] = $msg;
		MessagesUnreadLoader::exportFile($studyId, $userId, $messages);
		return $msg->sent;
	}
	public function deleteMessage(int $studyId, string $userId, int $sentTimestamp) {
		$messages = MessagesPendingLoader::importFile($studyId, $userId);
		
		foreach($messages as $index => $cMsg) {
			if($cMsg->sent == $sentTimestamp) {
				array_splice($messages, $index, 1);
				break;
			}
		}
		
		MessagesPendingLoader::exportFile($studyId, $userId, $messages);
	}
}