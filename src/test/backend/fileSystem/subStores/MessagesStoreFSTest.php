<?php

namespace test\backend\fileSystem\subStores;

use backend\Configs;
use backend\dataClasses\Message;
use backend\dataClasses\MessageParticipantInfo;
use backend\dataClasses\MessagesList;
use backend\fileSystem\loader\MessagesArchivedLoader;
use backend\fileSystem\loader\MessagesPendingLoader;
use backend\fileSystem\loader\MessagesUnreadLoader;
use backend\fileSystem\PathsFS;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class MessagesStoreFSTest extends BaseDataFolderTestSetup {
	
	protected function tearDown(): void {
		parent::tearDown();
		self::tearDownAfterClass();
	}
	protected function setUp(): void {
		parent::setUp();
		self::setUpBeforeClass();
	}
	
	function test_getStudiesWithUnreadMessagesForPermission_as_admin() {
		$username = self::$username;
		
		self::createEmptyStudy(123);
		self::createEmptyStudy(234);
		self::createEmptyStudy(345);
		
		$messages = [];
		$messages[] = new Message('userId', 'content');
		MessagesUnreadLoader::exportFile(123, 'userId', $messages);
		MessagesUnreadLoader::exportFile(345, 'userId', $messages);
		
		$this->login($username);
		$this->assertEquals([123, 345], Configs::getDataStore()->getMessagesStore()->getStudiesWithUnreadMessagesForPermission());
	}
	function test_getStudiesWithUnreadMessagesForPermission_as_user() {
		$studyId = 123;
		$username = 'otherUser';

		self::createEmptyStudy($studyId);
		self::createEmptyStudy(234);
		self::createEmptyStudy(345);

		$messages = [];
		$messages[] = new Message('userId', 'content');
		MessagesUnreadLoader::exportFile($studyId, 'userId', $messages);
		MessagesUnreadLoader::exportFile(345, 'userId', $messages);
		
		$this->login($username);
		$this->addPermission('msg', $studyId, $username);
		$this->assertEquals([$studyId], Configs::getDataStore()->getMessagesStore()->getStudiesWithUnreadMessagesForPermission());
	}
	
	function test_getMessagesList() {
		$studyId = 123;
		$userId = 'userId';
		self::createEmptyStudy($studyId);
		
		$archivedMessages = [
			new Message($userId, 'content'),
			new Message($userId, 'content')
		];
		$pendingMessages = [
			new Message($userId, 'content'),
		];
		$unreadMessages = [
			new Message($userId, 'content'),
			new Message($userId, 'content'),
			new Message($userId, 'content')
		];
		MessagesArchivedLoader::exportFile($studyId, $userId, $archivedMessages);
		MessagesPendingLoader::exportFile($studyId, $userId, $pendingMessages);
		MessagesUnreadLoader::exportFile($studyId, $userId, $unreadMessages);
		
		MessagesUnreadLoader::exportFile($studyId, 'userId2', $unreadMessages);
		
		$this->assertEquals(
			MessagesList::get($archivedMessages, $pendingMessages, $unreadMessages),
			Configs::getDataStore()->getMessagesStore()->getMessagesList($studyId, $userId)
		);
	}
	
	function test_getParticipantsWithMessages() {
		$studyId = 123;
		self::createEmptyStudy($studyId);
		$msg = new Message('userId', 'content');
		
		MessagesArchivedLoader::exportFile($studyId, 'userId1', [$msg]);
		MessagesPendingLoader::exportFile($studyId, 'userId2', [$msg]);
		MessagesPendingLoader::exportFile($studyId, 'userId1', [$msg]);
		MessagesUnreadLoader::exportFile($studyId, 'userId3', [$msg]);
		MessagesUnreadLoader::exportFile($studyId, 'userId4', [$msg]);
		
		$timeUser1 = filemtime(PathsFS::fileMessageArchive($studyId, 'userId1')) * 1000;
		$timeUser2 = filemtime(PathsFS::fileMessagePending($studyId, 'userId2')) * 1000;
		$timeUser3 = filemtime(PathsFS::fileMessageUnread($studyId, 'userId3')) * 1000;
		$timeUser4 = filemtime(PathsFS::fileMessageUnread($studyId, 'userId4')) * 1000;
		
		$info1 = new MessageParticipantInfo('userId1', $timeUser1);
		$info1->archived = true;
		$info1->pending = true;
		
		$info2 = new MessageParticipantInfo('userId2', $timeUser2);
		$info2->pending = true;
		
		$info3 = new MessageParticipantInfo('userId3', $timeUser3);
		$info3->unread = true;
		
		$info4 = new MessageParticipantInfo('userId4', $timeUser4);
		$info4->unread = true;
		
		$this->assertEquals(
			[
				$info1,
				$info2,
				$info3,
				$info4
			],
			Configs::getDataStore()->getMessagesStore()->getParticipantsWithMessages($studyId)
		);
	}
	
	function test_updateOrArchivePendingMessages() {
		$studyId = 123;
		self::createEmptyStudy($studyId);
		
		$messagesStore = Configs::getDataStore()->getMessagesStore();
		
		$messagesStore->sendMessage($studyId, 'userId', 'from', 'content1');
		$messagesStore->sendMessage($studyId, 'userId', 'from', 'content2');
		$messagesStore->sendMessage($studyId, 'userId', 'from', 'content3');
		$messagesStore->sendMessage($studyId, 'userId', 'from', 'content4');
		
		$expectedPendingMessages = $messagesStore->getMessagesList($studyId, 'userId')['pending'];
		$this->assertCount(4, $expectedPendingMessages);
		$this->assertEquals('content1', $expectedPendingMessages[0]->content);
		$this->assertEquals('content2', $expectedPendingMessages[1]->content);
		$this->assertEquals('content3', $expectedPendingMessages[2]->content);
		$this->assertEquals('content4', $expectedPendingMessages[3]->content);
		
		$messagesStore->updateOrArchivePendingMessages($studyId, 'userId', function(Message $message): bool {
			if($message->content == 'content1') {
				$message->content = 'new1';
				return true;
			}
			else if($message->content == 'content3') {
				$message->content = 'new3';
				return true;
			}
			return false;
		});
		
		
		
		$expectedPendingMessages = $messagesStore->getMessagesList($studyId, 'userId')['pending'];
		$this->assertCount(2, $expectedPendingMessages);
		$this->assertEquals('new1', $expectedPendingMessages[0]->content);
		$this->assertEquals('new3', $expectedPendingMessages[1]->content);
	}
	
	function test_setMessagesAsRead() {
		$studyId = 123;
		$userId = 'userId';
		$timestamp1 = 111;
		$timestamp2 = 222;
		$timestamp3 = 333;
		self::createEmptyStudy($studyId);
		
		$msg1 = new Message($userId, 'content');
		$msg1->sent = $timestamp1;
		
		$msg2 = new Message($userId, 'content');
		$msg2->sent = $timestamp2;
		
		$msg3 = new Message($userId, 'content');
		$msg3->sent = $timestamp3;
		
		MessagesUnreadLoader::exportFile($studyId, $userId, [
			$msg1,
			$msg2,
			$msg3
		]);
		
		MessagesUnreadLoader::exportFile($studyId, 'userId2', [
			$msg1
		]);
		
		$messagesStore = Configs::getDataStore()->getMessagesStore();
		$this->assertEquals(
			MessagesList::get([], [], [$msg1, $msg2, $msg3]),
			$messagesStore->getMessagesList($studyId, $userId)
		);
		
		$messagesStore->setMessagesAsRead($studyId, $userId, [$timestamp1, $timestamp2]);
		
		$msg1->archived = true;
		$msg2->archived = true;
		$this->assertEquals(
			MessagesList::get([$msg1, $msg2], [], [$msg3]),
			$messagesStore->getMessagesList($studyId, $userId)
		);
	}
	
	function test_sendMessage_receiveMessage_and_deleteMessage() {
		$studyId = 123;
		$userId = 'userId';
		self::createEmptyStudy($studyId);
		
		
		$messagesStore = Configs::getDataStore()->getMessagesStore();
		$sentTimestamp = $messagesStore->sendMessage($studyId, $userId, 'UnitTester', 'content');
		
		$sentMsg = new Message('UnitTester', 'content');
		$sentMsg->pending = true;
		$sentMsg->sent = $sentTimestamp;
		
		$this->assertEquals(
			MessagesList::get([], [$sentMsg], []),
			$messagesStore->getMessagesList($studyId, $userId)
		);
		
		
		$receivedTimestamp = $messagesStore->receiveMessage($studyId, $userId, $userId, 'content2');
		
		$receivedMsg = new Message($userId, 'content2');
		$receivedMsg->unread = true;
		$receivedMsg->sent = $receivedTimestamp;
		
		$this->assertEquals(
			MessagesList::get([], [$sentMsg], [$receivedMsg]),
			$messagesStore->getMessagesList($studyId, $userId)
		);
		
		
		$messagesStore->deleteMessage($studyId, $userId, $sentTimestamp);
		
		$this->assertEquals(
			MessagesList::get([], [], [$receivedMsg]),
			$messagesStore->getMessagesList($studyId, $userId)
		);
	}
	
	
}