<?php

namespace backend\fileSystem\subStores;

use backend\Configs;
use backend\Main;
use testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ . '/../../../autoload.php';

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
		
		$list = $loginTokenStore->getLoginTokenList($accountName);
		$this->assertEquals($tokenId1, $list[0]->tokenId);
		$this->assertFalse($list[0]->current);
		
		$this->assertEquals($tokenId2, $list[1]->tokenId);
		$this->assertTrue($list[1]->current);
		
		$this->assertEquals($tokenId3, $list[2]->tokenId);
		$this->assertFalse($list[2]->current);
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