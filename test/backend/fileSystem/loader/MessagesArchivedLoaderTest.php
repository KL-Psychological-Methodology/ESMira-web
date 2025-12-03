<?php

namespace backend\fileSystem\loader;

use backend\fileSystem\loader\MessagesArchivedLoader;
use testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ . '/../../../autoload.php';

class MessagesArchivedLoaderTest extends BaseDataFolderTestSetup {
	function test_import_and_export() {
		$studyId = 123;
		$this->createEmptyStudy($studyId);
		$messages = [['content' => 'message1'], ['content' => 'message2']];
		
		MessagesArchivedLoader::exportFile($studyId, 'userId', $messages);
		
		$exported = MessagesArchivedLoader::importFile($studyId, 'userId');
		$this->assertEquals($messages, $exported);
	}
}