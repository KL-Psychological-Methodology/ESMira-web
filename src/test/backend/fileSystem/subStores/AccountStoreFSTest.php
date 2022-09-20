<?php
declare(strict_types=1);

namespace test\backend\fileSystem\subStores;

require_once __DIR__ . '/../../../../backend/autoload.php';

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\Main;
use test\testConfigs\BaseDataFolderTestSetup;

class AccountStoreFSTest extends BaseDataFolderTestSetup {
	private $newAccountName = 'newUser';
	protected function tearDown(): void {
		$accountStore = Configs::getDataStore()->getAccountStore();
		foreach($accountStore->getAccountList() as $accountName) {
			$accountStore->removeAccount($accountName);
		}
	}
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		//remove default admin account:
		$accountStore = Configs::getDataStore()->getAccountStore();
		foreach($accountStore->getAccountList() as $accountName) {
			$accountStore->removeAccount($accountName);
		}
	}
	
	function test_addToLoginHistoryEntry_and_getLoginHistoryCsv() {
		$accountName = $this->newAccountName;
		
		$accountStore = Configs::getDataStore()->getAccountStore();
		
		$delimiter = Configs::get('csv_delimiter');
		$header = Main::arrayToCSV(['login', 'ip', 'userAgent'], $delimiter);
		
		$data = $header;
		$this->assertEquals($data, $accountStore->getLoginHistoryCsv($accountName));
		
		$accountStore->addToLoginHistoryEntry($accountName, ['data']);
		$data .= "\n" .Main::arrayToCSV(['data'], $delimiter);
		$this->assertEquals($data, $accountStore->getLoginHistoryCsv($accountName));
		
		usleep(1000000); //wait one second to have a difference in file creation time
		
		$accountStore->addToLoginHistoryEntry($accountName, ['data2']);
		$data .= "\n" .Main::arrayToCSV(['data2'], $delimiter);
		$this->assertEquals($data, $accountStore->getLoginHistoryCsv($accountName));
		
		
		$accountStore->addToLoginHistoryEntry($accountName, ['data3']);
		$data .= "\n" .Main::arrayToCSV(['data3'], $delimiter);
		$this->assertEquals($data, $accountStore->getLoginHistoryCsv($accountName));
		
		//delete the oldest entry:
		$accountStore->addToLoginHistoryEntry($accountName, ['data4'], 0);
		$data = $header ."\n" .Main::arrayToCSV(['data2'], $delimiter) ."\n" .Main::arrayToCSV(['data3'], $delimiter) ."\n" .Main::arrayToCSV(['data4'], $delimiter);
		$this->assertEquals($data, $accountStore->getLoginHistoryCsv($accountName));
	}
	
	function test_addStudyPermission_and_getPermissions_and_removeStudyPermission() {
		$accountName = 'newUser';
		$accountStore = Configs::getDataStore()->getAccountStore();
		
		$this->assertEquals([], $accountStore->getPermissions($accountName));
		$accountStore->addStudyPermission($accountName, 123, 'write');
		$accountStore->addStudyPermission($accountName, 345, 'write');
		$accountStore->addStudyPermission($accountName, 345, 'read');
		$this->assertEquals(['write' => [123, 345], 'read' => [345]], $accountStore->getPermissions($accountName));
		
		$accountStore->removeStudyPermission($accountName, 345, 'write');
		$this->assertEquals(['write' => [123], 'read' => [345]], $accountStore->getPermissions($accountName));
		
		$accountStore->removeStudyPermission($accountName, 345, 'read');
		$this->assertEquals(['write' => [123]], $accountStore->getPermissions($accountName));
		
		$accountStore->removeStudyPermission($accountName, 123, 'write');
		$this->assertEquals([], $accountStore->getPermissions($accountName));
		
		$accountStore->removeStudyPermission($accountName, 999, 'notExisting'); //should not lead to exception
	}
	
	function test_setAdminPermission() {
		$accountName = $this->newAccountName;
		$accountStore = Configs::getDataStore()->getAccountStore();
		
		$this->assertEquals([], $accountStore->getPermissions($accountName));
		
		$accountStore->setAdminPermission($accountName, true);
		$this->assertEquals(['admin' => true], $accountStore->getPermissions($accountName));
		
		$accountStore->setAdminPermission($accountName, false);
		$this->assertEquals(['admin' => false], $accountStore->getPermissions($accountName));
	}
	
	function test_get_and_remove_userBlockedTime() {
		$accountName = $this->newAccountName;
		$accountStore = Configs::getDataStore()->getAccountStore();
		$this->assertEquals(0, $accountStore->getAccountBlockedTime($accountName));
		
		$max = Configs::get('max_blocked_seconds_for_login');
		
		$accountStore->createBlocking($accountName);
		for($block = 1; $block < $max; $block *= 2) {
			$accountStore->createBlocking($accountName);
			$this->assertGreaterThan($block, $accountStore->getAccountBlockedTime($accountName));
		}
		$secs = $accountStore->getAccountBlockedTime($accountName);
		usleep(1000000);
		$this->assertEquals($secs - 1, $accountStore->getAccountBlockedTime($accountName));
		
		$accountStore->removeBlocking($accountName);
		$this->assertEquals(0, $accountStore->getAccountBlockedTime($accountName));
	}
	
	function test_add_and_remove_user() {
		$accountStore = Configs::getDataStore()->getAccountStore();
		
		$accountStore->setAccount('user1', 'pass1');
		$accountStore->setAccount('user2', 'pass2');
		$accountStore->setAccount('user3', 'pass3');
		
		$this->assertEquals(['user1', 'user2', 'user3'], $accountStore->getAccountList());
		
		$accountStore->removeAccount('user2');
		$this->assertEquals(['user1', 'user3'], $accountStore->getAccountList());
		
		$accountStore->removeAccount('user1');
		$this->assertEquals(['user3'], $accountStore->getAccountList());
		
	}
	
	function test_checkAccountLogin() {
		$accountStore = Configs::getDataStore()->getAccountStore();
		
		$accountStore->setAccount('user1', 'pass1');
		$accountStore->setAccount('user2', 'pass2');
		$accountStore->setAccount('user3', 'pass3');
		
		$this->assertTrue($accountStore->checkAccountLogin('user1', 'pass1'));
		$this->assertFalse($accountStore->checkAccountLogin('user1', 'pass2'));
		$this->assertFalse($accountStore->checkAccountLogin('user1', 'pass3'));
		$this->assertFalse($accountStore->checkAccountLogin('user1', 'passOther'));
		
		$this->assertFalse($accountStore->checkAccountLogin('user2', 'pass1'));
		$this->assertTrue($accountStore->checkAccountLogin('user2', 'pass2'));
		$this->assertFalse($accountStore->checkAccountLogin('user2', 'pass3'));
		$this->assertFalse($accountStore->checkAccountLogin('user2', 'passOther'));
		
		$this->assertFalse($accountStore->checkAccountLogin('user3', 'pass1'));
		$this->assertFalse($accountStore->checkAccountLogin('user3', 'pass2'));
		$this->assertTrue($accountStore->checkAccountLogin('user3', 'pass3'));
		$this->assertFalse($accountStore->checkAccountLogin('user3', 'passOther'));
		
		$this->assertFalse($accountStore->checkAccountLogin('userOther', 'pass1'));
		$this->assertFalse($accountStore->checkAccountLogin('userOther', 'pass2'));
		$this->assertFalse($accountStore->checkAccountLogin('userOther', 'pass3'));
		$this->assertFalse($accountStore->checkAccountLogin('userOther', 'passOther'));
		
		$accountStore->removeAccount('user2');
		$this->assertFalse($accountStore->checkAccountLogin('user2', 'pass2'));
	}
	
	function test_change_accountName() {
		$accountStore = Configs::getDataStore()->getAccountStore();
		$accountStore->setAccount('user1', 'pass1');
		$accountStore->setAccount('user2', 'pass2');
		$this->assertTrue($accountStore->checkAccountLogin('user1', 'pass1'));
		$this->assertTrue($accountStore->checkAccountLogin('user2', 'pass2'));
		
		//change accountName
		$accountStore->changeAccountName('user1', 'newUser1');
		$this->assertFalse($accountStore->checkAccountLogin('user1', 'pass1'));
		$this->assertTrue($accountStore->checkAccountLogin('newUser1', 'pass1'));
	}
	
	function test_change_that_has_login_token() {
		$accountStore = Configs::getDataStore()->getAccountStore();
		$loginTokenStore = Configs::getDataStore()->getLoginTokenStore();
		
		$accountStore->setAccount('user1', 'pass1');
		$loginTokenStore->saveLoginToken('user1', 'hash', 'tokenId');
		$this->assertEquals('hash', $loginTokenStore->getLoginToken('user1', 'tokenId'));
		
		//change accountName
		$accountStore->changeAccountName('user1', 'newUser1');
		$this->assertEquals('hash', $loginTokenStore->getLoginToken('newUser1', 'tokenId'));
	}
	
	function test_change_accountName_that_does_not_exist() {
		$accountStore = Configs::getDataStore()->getAccountStore();
		$accountStore->setAccount('user1', 'pass1');
		
		
		//change account that does not exist
		$this->expectException(CriticalException::class);
		$accountStore->changeAccountName('notExisting', 'newUser2');
	}
	
	function test_change_accountName_into_already_existing() {
		$accountStore = Configs::getDataStore()->getAccountStore();
		$accountStore->setAccount('user1', 'pass1');
		$accountStore->setAccount('user2', 'pass2');
		
		
		//change account into already existing accountName
		$this->expectException(CriticalException::class);
		$accountStore->changeAccountName('user1', 'user2');
	}
	
	function test_change_password() {
		$accountStore = Configs::getDataStore()->getAccountStore();
		$accountStore->setAccount('user1', 'pass1');
		$this->assertTrue($accountStore->checkAccountLogin('user1', 'pass1'));
		
		$accountStore->setAccount('user1', 'PASS2');
		$this->assertFalse($accountStore->checkAccountLogin('user1', 'pass1'));
		$this->assertTrue($accountStore->checkAccountLogin('user1', 'PASS2'));
	}
}