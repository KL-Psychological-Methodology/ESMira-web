<?php
declare(strict_types=1);

namespace test\backend\fileSystem\subStores;

require_once __DIR__ . '/../../../../backend/autoload.php';

use backend\fileSystem\loader\StudyAccessKeyIndexLoader;
use backend\fileSystem\subStores\StudyAccessIndexStoreFS;
use test\testConfigs\BaseDataFolderTestSetup;
use test\testConfigs\BaseTestSetup;


class StudyAccessIndexStoreFSTest extends BaseTestSetup {
	/**
	 * @var StudyAccessIndexStoreFS
	 */
	private $index;
	
	function setUp(): void {
		BaseDataFolderTestSetup::setUpBeforeClass();
		$this->index = new StudyAccessIndexStoreFS();
	}
	function tearDown(): void {
		BaseDataFolderTestSetup::tearDownAfterClass();
	}
	
	function test_getStudyIdForQuestionnaireId() {
		$studyId = 111;
		$studyId2 = 112;
		$this->index->addQuestionnaireKeys((object)[
			'id' => $studyId,
			'questionnaires' => [
				(object) ['internalId' => 12],
				(object) ['internalId' => 13]
			]
		]);
		$this->index->addQuestionnaireKeys((object)[
			'id' => $studyId2,
			'questionnaires' => [
				(object) ['internalId' => 14],
				(object) ['internalId' => 15],
				(object) ['internalId' => 16]
			]
		]);
		
		$this->assertEquals($studyId, $this->index->getStudyIdForQuestionnaireId(12));
		$this->assertEquals($studyId, $this->index->getStudyIdForQuestionnaireId(13));
		$this->assertEquals($studyId2, $this->index->getStudyIdForQuestionnaireId(14));
		$this->assertEquals($studyId2, $this->index->getStudyIdForQuestionnaireId(15));
		$this->assertEquals($studyId2, $this->index->getStudyIdForQuestionnaireId(16));
		$this->assertEquals(-1, $this->index->getStudyIdForQuestionnaireId(17));
	}
	
	function test_add_and_remove_studies() {
		$studyId1 = 111;
		$studyId2 = 112;
		$accessKey = 'accessKey';
		
		$this->assertFalse($this->index->accessKeyExists($accessKey));
		
		$this->index->add($studyId1);
		$this->index->add($studyId2);
		$this->assertEquals([$studyId1, $studyId2], $this->index->getStudyIds('~open'));
		
		$this->assertFalse($this->index->accessKeyExists($accessKey));
		$this->index->add($studyId1, $accessKey);
		$this->index->add($studyId2, $accessKey);
		$this->assertTrue($this->index->accessKeyExists($accessKey));
		$this->assertEquals([$studyId1, $studyId2], $this->index->getStudyIds($accessKey));
		
		$this->index->removeStudy($studyId2);
		
		$this->assertEquals([$studyId1], $this->index->getStudyIds('~open'));
		$this->assertEquals([$studyId1], $this->index->getStudyIds($accessKey));
		
		$this->index->removeStudy($studyId1);
		
		
		$this->assertEquals([], $this->index->getStudyIds('~open'));
		$this->assertEquals([], $this->index->getStudyIds($accessKey));
	}
	
	function test_addQuestionnaireKeys() {
		$studyId = 111;
		$this->index->addQuestionnaireKeys((object)[
			'id' => $studyId,
			'questionnaires' => [
				(object) ['internalId' => 12],
				(object) ['internalId' => 13]
			]
		]);
		
		
		
		$this->assertEquals([$studyId], $this->index->getStudyIds('~12'));
		$this->assertEquals([$studyId], $this->index->getStudyIds('~13'));
	}
	
	function test_saveChanges() {
		BaseDataFolderTestSetup::setUpBeforeClass();
		
		$this->assertEmpty(StudyAccessKeyIndexLoader::importFile());
		
		$this->index->saveChanges();
		$this->assertEmpty(StudyAccessKeyIndexLoader::importFile());
		
		$this->index->add(123);
		$this->index->saveChanges();
		$this->assertNotEmpty(StudyAccessKeyIndexLoader::importFile());
	}
}