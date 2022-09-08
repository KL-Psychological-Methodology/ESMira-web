<?php
declare(strict_types=1);

namespace test\backend\fileSystem\subStores;

require_once __DIR__ . '/../../../../backend/autoload.php';

use backend\Configs;
use backend\CriticalError;
use backend\Main;
use test\testConfigs\BaseDataFolderTestSetup;

class UserStoreFSTest extends BaseDataFolderTestSetup {
	private $newUsername = 'newUser';
	protected function tearDown(): void {
		$userStore = Configs::getDataStore()->getUserStore();
		foreach($userStore->getUserList() as $username) {
			$userStore->removeUser($username);
		}
	}
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		//remove default admin user:
		$userStore = Configs::getDataStore()->getUserStore();
		foreach($userStore->getUserList() as $username) {
			$userStore->removeUser($username);
		}
	}
	
	function test_addToLoginHistoryEntry_and_getLoginHistoryCsv() {
		$username = $this->newUsername;
		
		$userStore = Configs::getDataStore()->getUserStore();
		
		$delimiter = Configs::get('csv_delimiter');
		$header = Main::arrayToCSV(['login', 'ip', 'userAgent'], $delimiter);
		
		$data = $header;
		$this->assertEquals($data, $userStore->getLoginHistoryCsv($username));
		
		$userStore->addToLoginHistoryEntry($username, ['data']);
		$data .= "\n" .Main::arrayToCSV(['data'], $delimiter);
		$this->assertEquals($data, $userStore->getLoginHistoryCsv($username));
		
		usleep(1000000); //wait one second to have a difference in file creation time
		
		$userStore->addToLoginHistoryEntry($username, ['data2']);
		$data .= "\n" .Main::arrayToCSV(['data2'], $delimiter);
		$this->assertEquals($data, $userStore->getLoginHistoryCsv($username));
		
		
		$userStore->addToLoginHistoryEntry($username, ['data3']);
		$data .= "\n" .Main::arrayToCSV(['data3'], $delimiter);
		$this->assertEquals($data, $userStore->getLoginHistoryCsv($username));
		
		//delete the oldest entry:
		$userStore->addToLoginHistoryEntry($username, ['data4'], 0);
		$data = $header ."\n" .Main::arrayToCSV(['data2'], $delimiter) ."\n" .Main::arrayToCSV(['data3'], $delimiter) ."\n" .Main::arrayToCSV(['data4'], $delimiter);
		$this->assertEquals($data, $userStore->getLoginHistoryCsv($username));
	}
	
	function test_addStudyPermission_and_getPermissions_and_removeStudyPermission() {
		$username = 'newUser';
		$userStore = Configs::getDataStore()->getUserStore();
		
		$this->assertEquals([], $userStore->getPermissions($username));
		$userStore->addStudyPermission($username, 123, 'write');
		$userStore->addStudyPermission($username, 345, 'write');
		$userStore->addStudyPermission($username, 345, 'read');
		$this->assertEquals(['write' => [123, 345], 'read' => [345]], $userStore->getPermissions($username));
		
		$userStore->removeStudyPermission($username, 345, 'write');
		$this->assertEquals(['write' => [123], 'read' => [345]], $userStore->getPermissions($username));
		
		$userStore->removeStudyPermission($username, 345, 'read');
		$this->assertEquals(['write' => [123]], $userStore->getPermissions($username));
		
		$userStore->removeStudyPermission($username, 123, 'write');
		$this->assertEquals([], $userStore->getPermissions($username));
		
		$userStore->removeStudyPermission($username, 999, 'notExisting'); //should not lead to exception
	}
	
	function test_setAdminPermission() {
		$username = $this->newUsername;
		$userStore = Configs::getDataStore()->getUserStore();
		
		$this->assertEquals([], $userStore->getPermissions($username));
		
		$userStore->setAdminPermission($username, true);
		$this->assertEquals(['admin' => true], $userStore->getPermissions($username));
		
		$userStore->setAdminPermission($username, false);
		$this->assertEquals(['admin' => false], $userStore->getPermissions($username));
	}
	
	function test_get_and_remove_userBlockedTime() {
		$username = $this->newUsername;
		$userStore = Configs::getDataStore()->getUserStore();
		$this->assertEquals(0, $userStore->getUserBlockedTime($username));
		
		$max = Configs::get('max_blocked_seconds_for_login');
		
		$userStore->createBlocking($username);
		for($block = 1; $block < $max; $block *= 2) {
			$userStore->createBlocking($username);
			$this->assertGreaterThan($block, $userStore->getUserBlockedTime($username));
		}
		$secs = $userStore->getUserBlockedTime($username);
		usleep(1000000);
		$this->assertEquals($secs - 1, $userStore->getUserBlockedTime($username));
		
		$userStore->removeBlocking($username);
		$this->assertEquals(0, $userStore->getUserBlockedTime($username));
	}
	
	function test_add_and_remove_user() {
		$userStore = Configs::getDataStore()->getUserStore();
		
		$userStore->setUser('user1', 'pass1');
		$userStore->setUser('user2', 'pass2');
		$userStore->setUser('user3', 'pass3');
		
		$this->assertEquals(['user1', 'user2', 'user3'], $userStore->getUserList());
		
		$userStore->removeUser('user2');
		$this->assertEquals(['user1', 'user3'], $userStore->getUserList());
		
		$userStore->removeUser('user1');
		$this->assertEquals(['user3'], $userStore->getUserList());
		
	}
	
	function test_checkUserLogin() {
		$userStore = Configs::getDataStore()->getUserStore();
		
		$userStore->setUser('user1', 'pass1');
		$userStore->setUser('user2', 'pass2');
		$userStore->setUser('user3', 'pass3');
		
		$this->assertTrue($userStore->checkUserLogin('user1', 'pass1'));
		$this->assertFalse($userStore->checkUserLogin('user1', 'pass2'));
		$this->assertFalse($userStore->checkUserLogin('user1', 'pass3'));
		$this->assertFalse($userStore->checkUserLogin('user1', 'passOther'));
		
		$this->assertFalse($userStore->checkUserLogin('user2', 'pass1'));
		$this->assertTrue($userStore->checkUserLogin('user2', 'pass2'));
		$this->assertFalse($userStore->checkUserLogin('user2', 'pass3'));
		$this->assertFalse($userStore->checkUserLogin('user2', 'passOther'));
		
		$this->assertFalse($userStore->checkUserLogin('user3', 'pass1'));
		$this->assertFalse($userStore->checkUserLogin('user3', 'pass2'));
		$this->assertTrue($userStore->checkUserLogin('user3', 'pass3'));
		$this->assertFalse($userStore->checkUserLogin('user3', 'passOther'));
		
		$this->assertFalse($userStore->checkUserLogin('userOther', 'pass1'));
		$this->assertFalse($userStore->checkUserLogin('userOther', 'pass2'));
		$this->assertFalse($userStore->checkUserLogin('userOther', 'pass3'));
		$this->assertFalse($userStore->checkUserLogin('userOther', 'passOther'));
		
		$userStore->removeUser('user2');
		$this->assertFalse($userStore->checkUserLogin('user2', 'pass2'));
	}
	
	function test_change_username() {
		$userStore = Configs::getDataStore()->getUserStore();
		$userStore->setUser('user1', 'pass1');
		$userStore->setUser('user2', 'pass2');
		$this->assertTrue($userStore->checkUserLogin('user1', 'pass1'));
		$this->assertTrue($userStore->checkUserLogin('user2', 'pass2'));
		
		//change username
		$userStore->changeUsername('user1', 'newUser1');
		$this->assertFalse($userStore->checkUserLogin('user1', 'pass1'));
		$this->assertTrue($userStore->checkUserLogin('newUser1', 'pass1'));
	}
	
	function test_change_that_has_login_token() {
		$userStore = Configs::getDataStore()->getUserStore();
		$loginTokenStore = Configs::getDataStore()->getLoginTokenStore();
		
		$userStore->setUser('user1', 'pass1');
		$loginTokenStore->saveLoginToken('user1', 'hash', 'tokenId');
		$this->assertEquals('hash', $loginTokenStore->getLoginToken('user1', 'tokenId'));
		
		//change username
		$userStore->changeUsername('user1', 'newUser1');
		$this->assertEquals('hash', $loginTokenStore->getLoginToken('newUser1', 'tokenId'));
	}
	
	function test_change_username_that_does_not_exist() {
		$userStore = Configs::getDataStore()->getUserStore();
		$userStore->setUser('user1', 'pass1');
		
		
		//change user that does not exist
		$this->expectException(CriticalError::class);
		$userStore->changeUsername('notExisting', 'newUser2');
	}
	
	function test_change_username_into_already_existing() {
		$userStore = Configs::getDataStore()->getUserStore();
		$userStore->setUser('user1', 'pass1');
		$userStore->setUser('user2', 'pass2');
		
		
		//change user into already existing username
		$this->expectException(CriticalError::class);
		$userStore->changeUsername('user1', 'user2');
	}
	
	function test_change_password() {
		$userStore = Configs::getDataStore()->getUserStore();
		$userStore->setUser('user1', 'pass1');
		$this->assertTrue($userStore->checkUserLogin('user1', 'pass1'));
		
		$userStore->setUser('user1', 'PASS2');
		$this->assertFalse($userStore->checkUserLogin('user1', 'pass1'));
		$this->assertTrue($userStore->checkUserLogin('user1', 'PASS2'));
	}
}