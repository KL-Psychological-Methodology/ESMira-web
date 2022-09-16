<?php

namespace test\backend\fileSystem\subStores;

use backend\Configs;
use backend\dataClasses\TokenInfo;
use backend\Main;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class LoginTokenStoreTest extends BaseDataFolderTestSetup {
	protected function tearDown(): void {
		$accountStore = Configs::getDataStore()->getAccountStore();
		$accountStore->removeAccount(self::$accountName);
	}
	
	function test_save_and_remove_loginToken() {
		$accountName = self::$accountName;
		$hash = 'hash';
		$tokenId = 'tokenId';
		$loginTokenStore = Configs::getDataStore()->getLoginTokenStore();
		
		$this->assertFalse($loginTokenStore->loginTokenExists($accountName, $tokenId));
		
		$loginTokenStore->saveLoginToken($accountName, $hash, $tokenId);
		$this->assertTrue($loginTokenStore->loginTokenExists($accountName, $tokenId));
		$this->assertEquals($hash, $loginTokenStore->getLoginToken($accountName, $tokenId));
		
		
		$loginTokenStore->removeLoginToken($accountName, $tokenId);
		$this->assertFalse($loginTokenStore->loginTokenExists($accountName, $tokenId));
	}
	
	function test_getLoginTokenList() {
		$accountName = self::$accountName;
		$hash = 'hash';
		$tokenId1 = 'tokenId1';
		$tokenId2 = 'tokenId2';
		$tokenId3 = 'tokenId3';
		
		Main::setCookie('tokenId', $tokenId2);
		
		$loginTokenStore = Configs::getDataStore()->getLoginTokenStore();
		$loginTokenStore->saveLoginToken($accountName, $hash, $tokenId1);
		$loginTokenStore->saveLoginToken($accountName, $hash, $tokenId2);
		$loginTokenStore->saveLoginToken($accountName, $hash, $tokenId3);
		
		$this->assertEquals([
			new TokenInfo($tokenId1, time(), false),
			new TokenInfo($tokenId2, time(), true),
			new TokenInfo($tokenId3, time(), false)
		], $loginTokenStore->getLoginTokenList($accountName));
	}
	
	function test_clearAllLoginToken() {
		$accountName = self::$accountName;
		$hash = 'hash';
		$loginTokenStore = Configs::getDataStore()->getLoginTokenStore();
		
		$loginTokenStore->saveLoginToken($accountName, $hash, 'id1');
		$loginTokenStore->saveLoginToken($accountName, $hash, 'id2');
		$loginTokenStore->saveLoginToken($accountName, $hash, 'id3');
		
		$this->assertTrue($loginTokenStore->loginTokenExists($accountName, 'id1'));
		$this->assertTrue($loginTokenStore->loginTokenExists($accountName, 'id2'));
		$this->assertTrue($loginTokenStore->loginTokenExists($accountName, 'id3'));
		$this->assertFalse($loginTokenStore->loginTokenExists($accountName, 'other'));
		$this->assertFalse($loginTokenStore->loginTokenExists('other', 'id1'));
		
		$loginTokenStore->clearAllLoginToken($accountName);
		$this->assertFalse($loginTokenStore->loginTokenExists($accountName, 'id1'));
		$this->assertFalse($loginTokenStore->loginTokenExists($accountName, 'id2'));
		$this->assertFalse($loginTokenStore->loginTokenExists($accountName, 'id3'));
	}
}