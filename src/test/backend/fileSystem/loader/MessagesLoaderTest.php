<?php

namespace test\backend\fileSystem\loader;

use backend\Configs;
use backend\fileSystem\loader\MessagesArchivedLoader;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

//we use MessagesArchivedLoader because it has getPath() defined
class MessagesLoaderTest extends BaseDataFolderTestSetup {
	private $studyId = 123;
	private $userId = 123;
	
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		
		$studySaver = Configs::getDataStore()->getStudyStore();
		$studySaver->saveStudy((object) ['_' => (object) ['id' => 123]], []);
	}
	
	protected function setUp(): void {
		parent::setUp();
		MessagesArchivedLoader::exportFile($this->studyId, $this->userId, []); //deletes the file
	}
	
	function test_import_and_export() {
		$messages = [['content' => 'message1'], ['content' => 'message2']];
		
		$this->assertEquals([], MessagesArchivedLoader::importFile($this->studyId, $this->userId));
		
		MessagesArchivedLoader::exportFile($this->studyId, $this->userId, $messages);
		
		$this->assertEquals($messages, MessagesArchivedLoader::importFile($this->studyId, $this->userId));
	}
	
	function test_import_and_export_with_keepOpen() {
		$messages = [['content' => 'message1'], ['content' => 'message2']];
		
		//file does not exist:
		$this->assertEquals([], MessagesArchivedLoader::importFile($this->studyId, $this->userId, true));
		MessagesArchivedLoader::exportFile($this->studyId, $this->userId, $messages);
		
		//file already exists:
		$this->assertEquals($messages, MessagesArchivedLoader::importFile($this->studyId, $this->userId, true));
		
		MessagesArchivedLoader::exportFile($this->studyId, $this->userId, []);
		$this->assertEquals([], MessagesArchivedLoader::importFile($this->studyId, $this->userId));
	}
}