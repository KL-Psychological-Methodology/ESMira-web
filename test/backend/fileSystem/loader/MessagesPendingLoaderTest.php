<?php

namespace backend\fileSystem\loader;

use backend\fileSystem\loader\MessagesPendingLoader;
use testConfigs\BaseDataFolderTestSetup;

require_once __DIR__ . '/../../../autoload.php';

class MessagesPendingLoaderTest extends BaseDataFolderTestSetup {
	function test_import_and_export() {
		$studyId = 123;
		$this->createEmptyStudy($studyId);
		$messages = [['content' => 'message1'], ['content' => 'message2']];
		
		MessagesPendingLoader::exportFile($studyId, 'userId', $messages);
		
		$exported = MessagesPendingLoader::importFile($studyId, 'userId');
		$this->assertEquals($messages, $exported);
	}
}