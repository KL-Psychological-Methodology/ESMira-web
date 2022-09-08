<?php

namespace test\backend\fileSystem\subStores;

use backend\Configs;
use backend\dataClasses\TokenInfo;
use backend\Main;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class LoginTokenStoreTest extends BaseDataFolderTestSetup {
	protected function tearDown(): void {
		$userStore = Configs::getDataStore()->getUserStore();
		$userStore->removeUser(self::$username);
	}
	
	function test_save_and_remove_loginToken() {
		$username = self::$username;
		$hash = 'hash';
		$tokenId = 'tokenId';
		$loginTokenStore = Configs::getDataStore()->getLoginTokenStore();
		
		$this->assertFalse($loginTokenStore->loginTokenExists($username, $tokenId));
		
		$loginTokenStore->saveLoginToken($username, $hash, $tokenId);
		$this->assertTrue($loginTokenStore->loginTokenExists($username, $tokenId));
		$this->assertEquals($hash, $loginTokenStore->getLoginToken($username, $tokenId));
		
		
		$loginTokenStore->removeLoginToken($username, $tokenId);
		$this->assertFalse($loginTokenStore->loginTokenExists($username, $tokenId));
	}
	
	function test_getLoginTokenList() {
		$username = self::$username;
		$hash = 'hash';
		$tokenId1 = 'tokenId1';
		$tokenId2 = 'tokenId2';
		$tokenId3 = 'tokenId3';
		
		Main::setCookie('tokenId', $tokenId2);
		
		$loginTokenStore = Configs::getDataStore()->getLoginTokenStore();
		$loginTokenStore->saveLoginToken($username, $hash, $tokenId1);
		$loginTokenStore->saveLoginToken($username, $hash, $tokenId2);
		$loginTokenStore->saveLoginToken($username, $hash, $tokenId3);
		
		$this->assertEquals([
			new TokenInfo($tokenId1, time(), false),
			new TokenInfo($tokenId2, time(), true),
			new TokenInfo($tokenId3, time(), false)
		], $loginTokenStore->getLoginTokenList($username));
	}
	
	function test_clearAllLoginToken() {
		$username = self::$username;
		$hash = 'hash';
		$loginTokenStore = Configs::getDataStore()->getLoginTokenStore();
		
		$loginTokenStore->saveLoginToken($username, $hash, 'id1');
		$loginTokenStore->saveLoginToken($username, $hash, 'id2');
		$loginTokenStore->saveLoginToken($username, $hash, 'id3');
		
		$this->assertTrue($loginTokenStore->loginTokenExists($username, 'id1'));
		$this->assertTrue($loginTokenStore->loginTokenExists($username, 'id2'));
		$this->assertTrue($loginTokenStore->loginTokenExists($username, 'id3'));
		$this->assertFalse($loginTokenStore->loginTokenExists($username, 'other'));
		$this->assertFalse($loginTokenStore->loginTokenExists('other', 'id1'));
		
		$loginTokenStore->clearAllLoginToken($username);
		$this->assertFalse($loginTokenStore->loginTokenExists($username, 'id1'));
		$this->assertFalse($loginTokenStore->loginTokenExists($username, 'id2'));
		$this->assertFalse($loginTokenStore->loginTokenExists($username, 'id3'));
	}
}