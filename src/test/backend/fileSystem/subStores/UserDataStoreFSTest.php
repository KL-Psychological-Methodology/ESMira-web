<?php
declare(strict_types=1);

namespace test\backend\fileSystem\subStores;

require_once __DIR__ . '/../../../../backend/autoload.php';

use backend\fileSystem\subStores\UserDataStoreFS;
use test\testConfigs\BaseDataFolderTestSetup;

class UserDataStoreFSTest extends BaseDataFolderTestSetup {
	private $studyId = 123;
	private $group = 5;
	
	function setUp(): void {
		parent::setUp();
		self::setUpBeforeClass();
		$this->createEmptyStudy($this->studyId);
	}
	function tearDown(): void {
		parent::tearDown();
		self::tearDownAfterClass();
	}
	
	function test_writeAndClose() {
		$userId = 'test1';
		$appType = 'UnitTest';
		$appVersion = '1';
		
		$userTokenSaver = new UserDataStoreFS($userId);
		$userTokenSaver->addDataSetForSaving($this->studyId, $this->group, $appType, $appVersion);
		$userTokenSaver->addDataSetForSaving($this->studyId, $this->group, $appType, $appVersion);
		$userTokenSaver->addDataSetForSaving($this->studyId, $this->group, $appType, $appVersion);
		$userTokenSaver->writeAndClose();
		
		//make sure token file is reloaded:
		$userTokenSaver = new UserDataStoreFS($userId);
		$userTokenSaver->addDataSetForSaving($this->studyId, $this->group, $appType, $appVersion);
		$userTokenSaver->writeAndClose();
		
		$userData = $userTokenSaver->getUserData($this->studyId);
		$this->assertEquals($this->group, $userData->group);
		$this->assertEquals(4, $userData->dataSetCount);
		$this->assertEquals($appType, $userData->appType);
		$this->assertEquals($appVersion, $userData->appVersion);
	}
	
	
	public function test_isOutdated() {
		$userId = 'test1';
		
		$userTokenSaver = new UserDataStoreFS($userId);
		
		//sets the expected token to -1:
		$userTokenSaver->addDataSetForSaving($this->studyId, $this->group, 'UnitTest', '1');
		
		$this->assertFalse($userTokenSaver->isOutdated($this->studyId, 555, false));//is not a reupload
		$this->assertTrue($userTokenSaver->isOutdated($this->studyId, 555, true));
		$this->assertFalse($userTokenSaver->isOutdated($this->studyId, -1, true));
	}
	
	public function test_getNewStudyTokens() {
		$userTokenSaver = new UserDataStoreFS('test1');
		
		$this->createEmptyStudy(147);
		$this->createEmptyStudy(258);
		$this->createEmptyStudy(369);
		
		$this->assertEquals([], $userTokenSaver->getNewStudyTokens());
		
		$userTokenSaver->addDataSetForSaving(147, $this->group, 'UnitTest', '1');
		$userTokenSaver->addDataSetForSaving(258, $this->group, 'UnitTest', '1');
		$userTokenSaver->addDataSetForSaving(369, $this->group, 'UnitTest', '1');
		
		$tokens = $userTokenSaver->getNewStudyTokens();
		$this->assertArrayHasKey(147, $tokens);
		$this->assertArrayHasKey(258, $tokens);
		$this->assertArrayHasKey(369, $tokens);
	}
}