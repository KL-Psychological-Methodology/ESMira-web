<?php

namespace test\api;

use backend\CriticalError;
use backend\JsonOutput;
use backend\Main;
use backend\subStores\MessagesStore;
use backend\subStores\StudyStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseApiTestSetup;

require_once __DIR__ .'/../../backend/autoload.php';

class SaveMessageTest extends BaseApiTestSetup {
	private $studyId = 123;
	private $userid = 'userId';
	private $content = 'content';
	private $hasException = false;
	private $isLocked = false;
	
	public function setUp(): void {
		parent::setUp();
		$this->hasException = false;
		$this->isLocked = false;
		$this->content = 'content';
		$this->userid = 'userid';
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$studyStore = $this->createDataMock(StudyStore::class, 'isLocked', function() {return $this->isLocked;});
		$this->createStoreMock('getStudyStore', $studyStore, $observer);
		
		$messagesStore = $this->createDataMock(MessagesStore::class, 'receiveMessage', function() {
			if($this->hasException)
				throw new CriticalError('Unit test exception');
			return 123456789;
		});
		$this->createStoreMock('getMessagesStore', $messagesStore, $observer);
		
		return $observer;
	}
	
	
	private function doPreparations(array $additional = []) {
		Main::$defaultPostInput = json_encode(array_merge([
			'studyId' => $this->studyId,
			'userId' => $this->userid,
			'content' => $this->content,
			'serverVersion' => Main::ACCEPTED_SERVER_VERSION,
		], $additional));
	}
	
	function test() {
		$this->doPreparations();
		require DIR_BASE .'/api/save_message.php';
		$this->expectOutputString(JsonOutput::successObj());
	}
	
	function test_with_error() {
		$this->hasException = true;
		$this->doPreparations();
		require DIR_BASE .'/api/save_message.php';
		$this->expectOutputString(JsonOutput::error('Unit test exception'));
	}
	
	function test_with_faulty_username() {
		$this->userid = 'user%Id';
		$this->doPreparations();
		require DIR_BASE .'/api/save_message.php';
		$this->expectOutputString(JsonOutput::error('User is faulty'));
	}
	
	function test_with_short_content() {
		$this->content = '1';
		$this->doPreparations();
		require DIR_BASE .'/api/save_message.php';
		$this->expectOutputString(JsonOutput::error('Message is too short'));
	}
	
	function test_when_study_is_locked() {
		$this->isLocked = true;
		$this->doPreparations();
		require DIR_BASE .'/api/save_message.php';
		$this->expectOutputString(JsonOutput::error('This study is locked'));
	}
	
	function test_with_outdated_app() {
		$this->doPreparations(['serverVersion' => Main::ACCEPTED_SERVER_VERSION - 1]);
		require DIR_BASE .'/api/save_message.php';
		$this->expectOutputString(JsonOutput::error('This app is outdated. Aborting'));
	}
	
	function test_with_missing_data() {
		$this->assertMissingData(
			['userId', 'studyId', 'content', 'serverVersion'],
			function($a) {
				Main::$defaultPostInput = json_encode($a);
				require DIR_BASE .'/api/save_message.php';
				$this->assertEquals(JsonOutput::error('Missing data'), ob_get_contents());
				ob_clean();
				return true;
			}
		);
	}
	
	function test_with_faulty_data() {
		Main::$defaultPostInput = 'faulty';
		$this->expectOutputString(JsonOutput::error('Unexpected data'));
		require DIR_BASE .'/api/save_message.php';
	}
	
	function test_without_init() {
		$this->assertIsInit('save_message');
	}
}