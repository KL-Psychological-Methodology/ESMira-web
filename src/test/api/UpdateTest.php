<?php

namespace test\api;

use backend\exceptions\CriticalException;
use backend\dataClasses\Message;
use backend\JsonOutput;
use backend\Main;
use backend\subStores\MessagesStore;
use backend\subStores\StudyMetadataStore;
use backend\subStores\StudyStore;
use PHPUnit\Framework\MockObject\Stub;
use stdClass;
use test\testConfigs\BaseApiTestSetup;

require_once __DIR__ .'/../../backend/autoload.php';

class UpdateTest extends BaseApiTestSetup {
	private $defaultStudyId = 123;
	private $lockedStudyId = 991;
	private $hasMessagesStudyId = 881;
	private $exceptionStudyId = 771;
	private $studyVersion = 3;
	private $userId= 'userId';
	private $accessKeys = ['key1', 'key2'];
	private $msgTimestamp = 123456789;
	private $studyConfig = ['config' => true];
	
	public function setUp(): void {
		parent::setUp();
		$this->isAdmin = false;
	}
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$studyStore = $this->createStub(StudyStore::class);
		$studyStore->method('isLocked')
			->willReturnCallback(function(int $studyId): bool {
				return $studyId == $this->lockedStudyId;
			});
		$studyStore->method('getStudyConfig')
			->willReturnCallback(function(int $studyId): stdClass {
				if($studyId == $this->exceptionStudyId)
					throw new CriticalException('Unit test exception');
				return (object) $this->studyConfig;
			});
		$this->createStoreMock('getStudyStore', $studyStore, $observer);
		
		
		$studyMetadataStore = $this->createStub(StudyMetadataStore::class);
		$studyMetadataStore->method('getAccessKeys')
			->willReturnCallback(function(): array {
				return $this->accessKeys;
			});
		$studyMetadataStore->method('getVersion')
			->willReturnCallback(function(): int {
				return $this->studyVersion;
			});
		$this->createStoreMock('getStudyMetadataStore', $studyMetadataStore, $observer);
		
		
		$messagesStore = $this->createStub(MessagesStore::class);
		$messagesStore->method('updateOrArchivePendingMessages')
			->willReturnCallback(function($studyId, $userId, callable $callback) {
				if($studyId != $this->hasMessagesStudyId)
					return;
				
				$expectedResponses = [
					true,
					false
				];
				
				$msg1 = new Message('from', 'content');
				$msg1->sent = $this->msgTimestamp+1;
				$msg2 = new Message('from', 'content');
				$msg2->sent = $this->msgTimestamp-1;
				$messages = [$msg1, $msg2];
				foreach($messages as $i => $message) {
					$response = $callback($message);
					$this->assertEquals($expectedResponses[$i], $response);
					if($response) {
						$this->assertNotEquals(0, $message->read);
						$this->assertEquals(1, $message->delivered);
					}
				}
			});
		$this->createStoreMock('getMessagesStore', $messagesStore, $observer);
		
		return $observer;
	}
	
	function doTest(int $studyId, array $datasetArray = [], array $mainArray = []) {
		Main::$defaultPostInput = json_encode(array_merge([
			'serverVersion' => Main::ACCEPTED_SERVER_VERSION,
			'userId' => $this->userId,
			'dataset' => [
				$studyId => array_merge([
					'version' => $this->studyVersion,
					'msgTimestamp' => $this->msgTimestamp,
					'accessKey' => $this->accessKeys[0]
				], $datasetArray)
			]
		], $mainArray));
		require DIR_BASE .'/api/update.php';
	}
	
	
	
	function test_without_response() {
		$this->doTest($this->defaultStudyId, ['version' => $this->studyVersion+1]);
		$this->expectOutputString(JsonOutput::successObj(new stdClass()));
	}
	
	function test_for_forced_study_update() {
		$this->doTest($this->defaultStudyId, ['forceStudyUpdate' => true, 'version' => $this->studyVersion+1]);
		$this->expectOutputString(JsonOutput::successObj([$this->defaultStudyId => ['study' => (object) $this->studyConfig]]));
	}
	
	function test_for_study_update() {
		$this->doTest($this->defaultStudyId, ['version' => $this->studyVersion-1]);
		$this->expectOutputString(JsonOutput::successObj([$this->defaultStudyId => ['study' => (object) $this->studyConfig]]));
	}
	
	function test_with_locked_study() {
		$this->doTest($this->lockedStudyId, ['version' => $this->studyVersion-1]);
		$this->expectOutputString(JsonOutput::successObj(new stdClass()));
	}
	
	function test_with_exception() {
		$this->doTest($this->exceptionStudyId, ['version' => $this->studyVersion-1]);
		$this->expectOutputString(JsonOutput::error('Unit test exception'));
	}
	
	function test_for_messages() {
		$msg = new Message('server', 'content');
		$msg->sent = $this->msgTimestamp+1;
		$msg->delivered = 1;
		$msg->read = -9999;
		$this->doTest($this->hasMessagesStudyId);
		$expectedOutput = JsonOutput::successObj([$this->hasMessagesStudyId => [
			'msgs' => [$msg]
		]]);
		$expectedRegex = str_replace(['[', ']', '-9999'], ['\[', '\]', '\d+'], $expectedOutput);
		$this->expectOutputRegex($expectedRegex);
	}
	
	function test_without_accessKey() {
		Main::$defaultPostInput = json_encode([
			'serverVersion' => Main::ACCEPTED_SERVER_VERSION,
			'userId' => $this->userId,
			'dataset' => [
				$this->defaultStudyId => [
					'version' => 1,
					'msgTimestamp' => 123456789,
				]
			]
		]);
		require DIR_BASE .'/api/update.php';
		$this->expectOutputString(JsonOutput::error('Wrong accessKey: '));
	}
	
	function test_with_wrong_accessKey() {
		$this->doTest($this->defaultStudyId, ['accessKey' => 'wrongKey']);
		$this->expectOutputString(JsonOutput::error('Wrong accessKey: wrongKey'));
	}
	
	function test_with_missing_line_data() {
		$this->assertMissingData(
			['version', 'msgTimestamp'],
			function($a) {
				Main::$defaultPostInput = json_encode([
					'serverVersion' => Main::ACCEPTED_SERVER_VERSION,
					'userId' => $this->userId,
					'dataset' => [
						$this->defaultStudyId =>$a
					]
				]);
				require DIR_BASE .'/api/update.php';
				$this->assertEquals(JsonOutput::error('Missing line data'), ob_get_contents());
				ob_clean();
				return true;
			}
		);
	}
	
	function test_with_outdated_app() {
		$this->doTest($this->defaultStudyId, [], ['serverVersion' => Main::ACCEPTED_SERVER_VERSION - 1]);
		$this->expectOutputString(JsonOutput::error('This app is outdated. Aborting'));
	}
	
	function test_with_missing_data() {
		$this->assertMissingData(
			['dataset', 'userId', 'serverVersion'],
			function($a) {
				Main::$defaultPostInput = json_encode($a);
				require DIR_BASE .'/api/update.php';
				$this->assertEquals(JsonOutput::error('Missing data'), ob_get_contents());
				ob_clean();
				return true;
			}
		);
	}
	
	function test_with_faulty_data() {
		Main::$defaultPostInput = 'faulty';
		$this->expectOutputString(JsonOutput::error('Missing data'));
		require DIR_BASE .'/api/update.php';
	}
	
	function test_without_init() {
		$this->assertIsInit('update');
	}
}