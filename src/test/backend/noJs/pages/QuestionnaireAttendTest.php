<?php

namespace backend\noJs\pages;

use backend\DataSetCache;
use backend\noJs\ForwardingException;
use backend\noJs\Lang;
use backend\ResponsesIndex;
use backend\subStores\ResponsesStore;
use backend\subStores\StudyAccessIndexStore;
use backend\subStores\StudyStore;
use backend\subStores\UserDataStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseNoJsTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class QuestionnaireAttendTest extends BaseNoJsTestSetup {
	protected $configs = [
		123 => ['id' => 123, 'title' => 'study1', 'webQuestionnaireCompletedInstructions' => 'completed', 'questionnaires' => [
			['internalId' => 1111, 'title'=>'questionnaire', 'pages' => [
				['randomized' => true, 'inputs' => [
					['name' => 'input11', 'responseType' => 'text_input'],
					['name' => 'input12', 'responseType' => 'binary'],
					['name' => 'input13', 'responseType' => 'date'],
					['name' => 'input14', 'responseType' => 'dynamic_input', 'subInputs' => [['responseType' => 'time']]],
				]],
				['inputs' => [
					['name' => 'input21', 'responseType' => 'image'],
					['name' => 'input22', 'responseType' => 'likert', 'required' => true],
					['name' => 'input23', 'responseType' => 'list_multiple', 'listChoices' => ['choice1', 'choice2']],
					['name' => 'input24', 'responseType' => 'list_single', 'listChoices' => ['choice1', 'choice2']],
				]],
				['inputs' => [
					['name' => 'input31', 'responseType' => 'number'],
					['responseType' => 'text'],
					['name' => 'input33', 'responseType' => 'time', 'forceInt' => true],
					['name' => 'input34', 'responseType' => 'va_scale'],
					['name' => 'input35', 'responseType' => 'video']
				]]
			]],
			['internalId' => 2222, 'publishedWeb' => false, 'pages' => [['inputs' => []]]]
		]],
		234 => ['id' => 234, 'publishedWeb' => false, 'questionnaires' => []],
		345 => ['id' => 345, 'informedConsentForm' => 'content', 'questionnaires' => [['internalId' => 4444, 'pages' => [['inputs' => []]]]]]
	];
	protected $questionnaireKeys = [
		'input11',
		'input12',
		'input13',
		'input14',
		'input21',
		'input22',
		'input23',
		'input24',
		'input31',
		'input32',
		'input33',
		'input34',
		'input35',
	];
	
	function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$studyStore = $this->createStub(StudyStore::class);
		$studyStore
			->method('getEventIndex')
			->willReturn(new ResponsesIndex([]));
		$studyStore
			->method('getQuestionnaireIndex')
			->willReturn(new ResponsesIndex($this->questionnaireKeys));
		$studyStore
			->method('questionnaireExists')
			->willReturn(true);
		
		$studyAccessIndexStore = $this->createStub(StudyAccessIndexStore::class);
		$studyAccessIndexStore
			->method('getStudyIdForQuestionnaireId')
			->willReturnCallback(function($internalId): array {
				return [123];
			});
		
		$this->setUpForStudyData($observer, $studyStore, $studyAccessIndexStore);
		
		
		$userDataStore = $this->createMock(UserDataStore::class);
		$userDataStore
			->method('addDataSetForSaving')
			->willReturn(true);
		$this->createStoreMock('getUserDataStore', $userDataStore, $observer);
		
		
		$responsesStore = $this->createMock(ResponsesStore::class);
		$responsesStore
			->method('saveDataSetCache')
			->willReturnCallback(function(string $userId, DataSetCache $cache, callable $success, callable $error) {
				$questionnaireCache = $cache->getQuestionnaireCache();
				$id = $cache->getEventCache()[123]->ids[0];
				if($questionnaireCache) {
					$container = $questionnaireCache[123][1111];
					$data = $container->data[$id];
					$this->assertEquals('value11', $data['input11']);
					$this->assertEquals('value12', $data['input12']);
					$this->assertEquals('value13', $data['input13']);
					$this->assertEquals('value14', $data['input14']);
					$this->assertEquals('value21', $data['input21']);
					$this->assertEquals('value22', $data['input22']);
					$this->assertEquals('value23,value232', $data['input23']);
					$this->assertEquals('value24', $data['input24']);
					$this->assertEquals('value31', $data['input31']);
					$this->assertEquals('90', $data['input33']);
					$this->assertEquals('value34', $data['input34']);
					$this->assertEquals('value35', $data['input35']);
				}
				
				if($this->hasSavingError)
					$error($id, 'UnitTest error');
				else
					$success($id);
			});
		$this->createStoreMock('getResponsesStore', $responsesStore, $observer);
		
		return $observer;
	}
	public function setUp(): void {
		parent::setUp();
		$this->hasSavingError = false;
		$_GET['qid'] = 1111;
		$_COOKIE["participant123"] = 'userId';
		
		if(!defined('SID'))
			define("SID", 'session_name=session'); //session_start() is not called but SID is used in QuestionnaireSaver::getSessionUrlParameter()
	}
	
	function test_with_non_web_study() {
		$_GET['id'] = 234;
		$this->expectErrorMessage(AppInstall::class);
		new QuestionnaireAttend();
	}
	
	function test_with_faulty_questionnaireId() {
		$_GET['qid'] = 9999;
		$this->expectErrorMessage(StudyOverview::class);
		new QuestionnaireAttend();
	}
	
	function test_without_participant() {
		unset($_COOKIE["participant123"]);
		$this->expectErrorMessage(GetParticipant::class);
		new QuestionnaireAttend();
	}
	
	function test_with_new_participant() {
		unset($_COOKIE["participant123"]);
		$this->setPost(['participant' => 'newUser', 'new_participant' => true]);
		$obj = new QuestionnaireAttend();
		$content = $obj->getContent();
		
		$this->assertStringContainsString('name="participant" value="newUser"', $content);
	}
	
	function test_with_deleting_participant() {
		unset($_COOKIE["participant123"]);
		$this->setPost(['participant' => 'userId', 'delete_participant' => true]);
		$this->expectErrorMessage(GetParticipant::class);
		new QuestionnaireAttend();
	}
	
	function test_with_not_active_questionnaire() {
		$_GET['qid'] = 2222;
		$obj = new QuestionnaireAttend();
		$content = $obj->getContent();
		$this->assertStringContainsString(Lang::get('error_questionnaire_not_active'), $content);
	}
	
	function test_without_needed_informed_consent() {
		$_GET['id'] = 345;
		$_GET['qid'] = 4444;
		$this->expectErrorMessage(InformedConsent::class);
		new QuestionnaireAttend();
	}
	
	function test_with_needed_informed_consent() {
		$_GET['id'] = 345;
		$_GET['qid'] = 4444;
		$_COOKIE["participant345"] = 'userId';
		$this->setPost(['informed_consent' => true]);
		$obj = new QuestionnaireAttend();
		$this->assertEquals(Lang::get('questionnaire'), $obj->getTitle());
	}
	
	function test_page1() {
		$obj = new QuestionnaireAttend();
		$study = $this->configs[123];
		$questionnaire = $study['questionnaires'][0];
		
		$content = $obj->getContent();
		$title = $obj->getTitle();
		
		$this->assertStringContainsString($questionnaire['title'], $title);
		$this->assertStringContainsString('1/3', $title);
		
		$this->assertStringContainsString('name="responses[input11]"', $content);
		$this->assertStringContainsString('name="responses[input12]"', $content);
		$this->assertStringContainsString('name="responses[input13]"', $content);
		$this->assertStringContainsString('name="responses[input14]"', $content);
		$this->assertStringContainsString('name="continue"', $content);
		$this->assertStringContainsString('name="participant" value="userId"', $content);
		$this->assertStringContainsString('name="informed_consent" value="1"', $content);
	}
	
	function test_page2() {
		$this->setPost(['continue' => true]);
		$obj = new QuestionnaireAttend();
		$study = $this->configs[123];
		$questionnaire = $study['questionnaires'][0];
		
		$content = $obj->getContent();
		$title = $obj->getTitle();
		
		$this->assertStringContainsString($questionnaire['title'], $title);
		$this->assertStringContainsString('2/3', $title);
		
		
		$this->assertStringContainsString('name="responses[input21]"', $content);
		$this->assertStringContainsString('name="responses[input22]"', $content);
		$this->assertStringContainsString('name="responses[input23][]"', $content);
		$this->assertStringContainsString('name="responses[input24]"', $content);
		$this->assertStringContainsString('name="continue"', $content);
		$this->assertStringContainsString('name="participant" value="userId"', $content);
		$this->assertEquals($_SESSION['questionnaire_1111']['currentPage'], 1);
		$this->assertStringContainsString('name="informed_consent" value="1"', $content);
	}
	
	function test_from_page2_with_missing_data() {
		$this->setPost(['continue' => true]);
		$obj = new QuestionnaireAttend();//page 2
		$obj = new QuestionnaireAttend();
		$study = $this->configs[123];
		$questionnaire = $study['questionnaires'][0];
		
		$content = $obj->getContent();
		$title = $obj->getTitle();
		
		$this->assertStringContainsString($questionnaire['title'], $title);
		$this->assertStringContainsString('2/3', $title);
		
		$this->assertStringContainsString('Please fill out all required fields', $content);
		$this->assertStringContainsString('name="responses[input21]"', $content);
		$this->assertStringContainsString('name="responses[input22]"', $content);
		$this->assertStringContainsString('name="responses[input23][]"', $content);
		$this->assertStringContainsString('name="responses[input24]"', $content);
		$this->assertStringContainsString('name="continue"', $content);
		$this->assertEquals(1, $_SESSION['questionnaire_1111']['currentPage']);
		$this->assertStringContainsString('name="participant" value="userId"', $content);
		$this->assertStringContainsString('name="informed_consent" value="1"', $content);
	}
	
	function test_page3() {
		$this->setPost([
			'continue' => true,
			'responses' => ['input21' => 'value21', 'input22' => 'value22', 'input23' => ['value23'], 'input24' => 'value24']
		]);
		$obj = new QuestionnaireAttend();//page 2
		$obj = new QuestionnaireAttend();
		$study = $this->configs[123];
		$questionnaire = $study['questionnaires'][0];
		
		$content = $obj->getContent();
		$title = $obj->getTitle();
		
		$this->assertStringContainsString($questionnaire['title'], $title);
		$this->assertStringContainsString('3/3', $title);
		
		$savedResponses = $_SESSION['questionnaire_1111']['responses'];
		$this->assertEquals('value21', $savedResponses['input21']);
		$this->assertEquals('value22', $savedResponses['input22']);
		$this->assertEquals('value23', $savedResponses['input23']);
		$this->assertEquals('value24', $savedResponses['input24']);
		$this->assertEquals(2, $_SESSION['questionnaire_1111']['currentPage']);
		
		$this->assertStringContainsString('name="responses[input31]"', $content);
		$this->assertStringContainsString('name="responses[input33]"', $content);
		$this->assertStringContainsString('name="responses[input34]"', $content);
		$this->assertStringContainsString('name="responses[input35]"', $content);
		$this->assertStringContainsString('name="save"', $content);
		$this->assertStringContainsString('name="participant" value="userId"', $content);
		$this->assertStringContainsString('name="informed_consent" value="1"', $content);
	}
	
	function test_save_after_page() {
		$this->setPost([
			'continue' => true,
			'responses' => [
				'input11' => 'value11',
				'input12' => 'value12',
				'input13' => 'value13',
				'input14' => 'value14',
			]
		]);
		$obj = new QuestionnaireAttend(); //to page 2
		$savedResponses = &$_SESSION['questionnaire_1111']['responses'];
		$this->assertEquals('value11', $savedResponses['input11']);
		$this->assertEquals('value12', $savedResponses['input12']);
		$this->assertEquals('value13', $savedResponses['input13']);
		$this->assertEquals('value14', $savedResponses['input14']);
		$this->assertStringContainsString('name="continue"', $obj->getContent());
		$this->assertStringContainsString('2/3', $obj->getTitle());
		
		$this->setPost([
			'continue' => true,
			'responses' => [
				'input21' => 'value21',
				'input22' => 'value22',
				'input23' => ['value23', 'value232'],
				'input24' => 'value24',
			]
		]);
		$obj = new QuestionnaireAttend(); //to page 3
		$this->assertEquals('value21', $savedResponses['input21']);
		$this->assertEquals('value22', $savedResponses['input22']);
		$this->assertEquals('value23,value232', $savedResponses['input23']);
		$this->assertEquals('value24', $savedResponses['input24']);
		$this->assertStringContainsString('name="save"', $obj->getContent());
		$this->assertStringContainsString('3/3', $obj->getTitle());
		
		$this->setPost([
			'save' => true,
			'responses' => [
				'input31' => 'value31',
				'input33' => '01:30',
				'input34' => 'value34',
				'input35' => 'value35'
			]
		]);
		$obj = new QuestionnaireAttend(); //finish
		$this->assertEquals('value31', $savedResponses['input31']);
		$this->assertEquals('90', $savedResponses['input33']);
		$this->assertEquals('value34', $savedResponses['input34']);
		$this->assertEquals('value35', $savedResponses['input35']);
		
		$this->assertStringContainsString('3/3', $obj->getTitle());
		
		$study = $this->configs[123];
		$questionnaire = $study['questionnaires'][0];
		
		$content = $obj->getContent();
		$this->assertStringContainsString($this->configs[123]['webQuestionnaireCompletedInstructions'], $content);
	}
}