<?php
declare(strict_types=1);

namespace test\backend\fileSystem;

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\fileSystem\ESMiraInitializerFS;
use backend\fileSystem\subStores\RewardCodeStoreFS;
use backend\fileSystem\subStores\ServerStoreFS;
use backend\fileSystem\subStores\StudyAccessIndexStoreFS;
use backend\fileSystem\subStores\StudyMetadataStoreFS;
use backend\fileSystem\subStores\StudyStatisticsMetadataStoreFS;
use backend\fileSystem\subStores\StudyStatisticsStoreFS;
use backend\fileSystem\subStores\StudyStoreFS;
use backend\fileSystem\subStores\UserDataStoreFS;
use backend\fileSystem\subStores\AccountStoreFS;
use backend\subStores\ServerStatisticsStore;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ . '/../../../backend/autoload.php';

class DataStoreFSTest extends BaseDataFolderTestSetup {
	private $studyId = 123;
	
	function tearDown(): void {
		try {
			Configs::getDataStore()->getStudyStore()->delete($this->studyId);
		}
		catch(CriticalException $e) {}
	}
	
	function test_getESMiraInitializer() {
		$this->assertInstanceOf(ESMiraInitializerFS::class, Configs::getDataStore()->getESMiraInitializer());
	}
	
	function test_getAccountStore() {
		$this->assertInstanceOf(AccountStoreFS::class, Configs::getDataStore()->getAccountStore());
	}
	function test_getAccessIndexStore() {
		$this->assertInstanceOf(StudyAccessIndexStoreFS::class, Configs::getDataStore()->getStudyAccessIndexStore());
	}
	function test_getStudyStore() {
		$this->assertInstanceOf(StudyStoreFS::class, Configs::getDataStore()->getStudyStore());
	}
	function test_getUserDataStore() {
		$this->assertInstanceOf(UserDataStoreFS::class, Configs::getDataStore()->getUserDataStore('user', 'UnitTest', '0.0.0'));
	}
	function test_getStudyMetadataStore() {
		$this->createEmptyStudy($this->studyId);
		$this->assertInstanceOf(StudyMetadataStoreFS::class, Configs::getDataStore()->getStudyMetadataStore($this->studyId));
	}
	function test_getStudyStatisticsMetadataStore() {
		$this->assertInstanceOf(StudyStatisticsMetadataStoreFS::class, Configs::getDataStore()->getStudyStatisticsMetadataStore($this->studyId));
	}
	function test_getStudyStatisticsStore() {
		$this->assertInstanceOf(StudyStatisticsStoreFS::class, Configs::getDataStore()->getStudyStatisticsStore($this->studyId));
	}
	function test_getStatisticsStore() {
		$this->assertInstanceOf(ServerStatisticsStore::class, Configs::getDataStore()->getServerStatisticsStore());
	}
	function test_getServerStore() {
		$this->assertInstanceOf(ServerStoreFS::class, Configs::getDataStore()->getServerStore());
	}
	function test_getRewardCodeStore() {
		$this->assertInstanceOf(RewardCodeStoreFS::class, Configs::getDataStore()->getRewardCodeStore());
	}
}