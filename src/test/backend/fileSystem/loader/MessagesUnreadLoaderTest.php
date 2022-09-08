<?php

namespace test\backend\fileSystem\loader;

use backend\fileSystem\loader\MessagesUnreadLoader;
use test\testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class MessagesUnreadLoaderTest extends BaseDataFolderTestSetup {
	function test_import_and_export() {
		$studyId = 123;
		$this->createEmptyStudy($studyId);
		$messages = [['content' => 'message1'], ['content' => 'message2']];
		
		MessagesUnreadLoader::exportFile($studyId, 'userId', $messages);
		
		$exported = MessagesUnreadLoader::importFile($studyId, 'userId');
		$this->assertEquals($messages, $exported);
	}
}