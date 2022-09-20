<?php
declare(strict_types=1);

namespace test\backend\fileSystem\subStores;

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\fileSystem\subStores\StudyMetadataStoreFS;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ . '/../../../../backend/autoload.php';


class StudyMetadataStoreFSTest extends BaseDataFolderTestSetup {
	private $studyId = 123;
	function tearDown(): void {
		try {
			Configs::getDataStore()->getStudyStore()->delete($this->studyId);
		}
		catch(CriticalException $e) {}
	}
	
	function test_metadata() {
		$version = 1;
		$accessKeys = ['accessKey1', 'accessKey2'];
		
		$this->createStudy((object) [
			'id' => $this->studyId,
			'version' => 1,
			'published' => true,
			'accessKeys' => $accessKeys
		], []);
		
		$metadata = new StudyMetadataStoreFS($this->studyId);
		$this->assertEquals($version, $metadata->getVersion());
		$this->assertTrue($metadata->isPublished());
		$this->assertEquals($accessKeys, $metadata->getAccessKeys());
		$this->assertGreaterThanOrEqual(time(), $metadata->getLastBackup());
	}
	
	function test_metadata_for_non_existing_study() {
		$this->expectException(CriticalException::class);
		$metadata = new StudyMetadataStoreFS($this->studyId);
		$metadata->getVersion();
	}
}