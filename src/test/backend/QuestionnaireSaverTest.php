<?php

namespace backend;

use backend\subStores\ResponsesStore;
use backend\subStores\StudyAccessIndexStore;
use backend\subStores\StudyStore;
use backend\subStores\UserDataStore;
use PHPUnit\Framework\MockObject\Stub;
use stdClass;
use test\testConfigs\BaseMockedTestSetup;
use test\testConfigs\SkipArgument;

require_once __DIR__ .'/../../backend/autoload.php';

class QuestionnaireSaverTest extends BaseMockedTestSetup {
	private function getQuestionnaireSaver(): QuestionnaireSaver {
		$studyId = 123;
		$_COOKIE["participant$studyId"] = 'testUser';
		return new QuestionnaireSaver((object)['id' => $studyId], (object)['internalId' => 1234, 'title' => 'Questionnaire']);
	}
	private function createObj(array $a): stdClass {
		if(empty($a))
			return new stdClass();
		return json_decode(json_encode($a));
	}
	function test_drawInput() {
		$input = $this->createObj([
			'name' => 'inputName',
		]);
		$output = $this->getQuestionnaireSaver()->drawInput($input);
		$this->assertStringContainsString('responses[inputName]', $output);
	}
	function test_drawInput_with_default_value() {
		$input = $this->createObj([
			'name' => 'inputName',
			'defaultValue' => 'value123'
		]);
		$output = $this->getQuestionnaireSaver()->drawInput($input);
		$this->assertStringContainsString('responses[inputName]', $output);
		$this->assertStringContainsString('value123', $output);
	}
	function test_drawInput_with_non_existing_type() {
		$input = $this->createObj([
			'name' => 'inputName',
			'responseType' => 'not existing'
		]);
		$output = $this->getQuestionnaireSaver()->drawInput($input);
		$this->assertStringContainsString('Broken Input', $output);
	}
	function test_drawInput_with_skipped_type() {
		$input = $this->createObj([
			'name' => 'inputName',
			'responseType' => 'app_usage'
		]);
		$output = $this->getQuestionnaireSaver()->drawInput($input);
		$this->assertEquals('', $output);
	}
	
	public function test_saveDataset() {
		//setup:
		$eventKeyIndex = 'indexKey';
		$observer = $this->createMock(DataStoreInterface::class);
		
		$studyStore = $this->createStub(StudyStore::class);
		$studyStore->method('getEventIndex') //needed so CreateDataSet is able to call saveDataSetCache()
		->willReturn(new ResponsesIndex([$eventKeyIndex]));
		$this->createStoreMock('getStudyStore', $studyStore, $observer);
		
		$studyStore = $this->createStub(UserDataStore::class); //needed so CreateDataSet is able to call saveDataSetCache()
		$studyStore->method('addDataSetForSaving')
			->willReturn(true);
		$this->createStoreMock('getUserDataStore', $studyStore, $observer);
		
		$this->createStoreMock(
			'getResponsesStore',
			$this->createDataMock(ResponsesStore::class, 'saveDataSetCache', function(string $userId, DataSetCache $cache, callable $success, callable $error) {
				$success(0);
			}),
			$observer
		);
		Configs::injectDataStore($observer);
		
		
		
		//test:
		$saver = $this->getQuestionnaireSaver();
		$saver->saveCache($eventKeyIndex, 'data');
		$saver->saveDataset(
			'joined',
			'userId',
			true
		);
		$cache = new DataSetCache();
		$cache->addToEventCache(123, 0, [$eventKeyIndex => 'data']);
		$cache->getEventIndex(123);
		$this->assertDataMock('saveDataSetCache', ['userId', $cache, new SkipArgument(), new SkipArgument()]);
	}
	
	
	function test_error() {
		$input = $this->createObj([
		]);
		$output = $this->getQuestionnaireSaver()->error($input);
		$this->assertStringContainsString('Broken Input', $output);
	}
	
	function test_text() {
		$input = $this->createObj([
			'text' => 'textContent',
		]);
		$output = $this->getQuestionnaireSaver()->text($input, false, 'name123', 'value123');
		$this->assertStringContainsString('textContent', $output);
		
		$output = $this->getQuestionnaireSaver()->text($input, true, 'name123', 'value123');
		$this->assertStringContainsString('textContent*', $output);
	}
	
	function test_binary() {
		$input = $this->createObj([]);
		$output = $this->getQuestionnaireSaver()->binary($input, false, 'name123', '0');
		$this->assertMatchesRegularExpression('/name="name123".+value="0".+checked="checked"/i', $output);
		$this->assertMatchesRegularExpression('/name="name123".+value="1"/i', $output);
		
		$output = $this->getQuestionnaireSaver()->binary($input, false, 'name123', '1');
		$this->assertMatchesRegularExpression('/name="name123".+value="0"/i', $output);
		$this->assertMatchesRegularExpression('/name="name123".+value="1".+checked="checked"/i', $output);
		
		$output = $this->getQuestionnaireSaver()->binary($input, false, 'name123', '');
		$this->assertStringNotContainsString('checked', $output);
		
		$output = $this->getQuestionnaireSaver()->binary($input, true, 'name123', '0');
		$this->assertMatchesRegularExpression('/name="name123".+value="0".+required="required"/i', $output);
		$this->assertMatchesRegularExpression('/name="name123".+value="1".+required="required"/i', $output);
	}
	
	function test_date() {
		$input = $this->createObj([]);
		$output = $this->getQuestionnaireSaver()->date($input, false, 'name123', 'value123');
		$this->assertStringContainsStringIgnoringCase('name123', $output);
		$this->assertStringContainsStringIgnoringCase('value123', $output);
		
		$output = $this->getQuestionnaireSaver()->date($input, true, 'name123', 'value123');
		$this->assertStringContainsStringIgnoringCase('name123', $output);
		$this->assertStringContainsStringIgnoringCase('value123', $output);
		$this->assertStringContainsStringIgnoringCase('required="required"', $output);
	}
	
	function assertOrderOfDynamicInput(stdClass $input, bool $expectedOrder, int $subInputCount = 4) {
		$_COOKIE = [
			'inputName123_saved' => -1
		];
		$rememberedPositions = [];
		$history = '';
		
		$ordered = true;
		$lastValue = 0;
		for($i=10; $i>=0; --$i) {
			//simulate filled out questionnaire:
			$lastCompletedCookieName = sprintf(QuestionnaireSaver::COOKIE_LAST_COMPLETED, 123, 1234);
			$_COOKIE[$lastCompletedCookieName] = 123456789;
			$dynamicTimeCookieName = sprintf(QuestionnaireSaver::COOKIE_DYNAMIC_TIME, 123, 1234, 'inputName');
			$_COOKIE[$dynamicTimeCookieName] = 123456789-1;
			
			$output = $this->getQuestionnaireSaver()->dynamic_input($input, true, 'name123', 'value123', 123, 1234);
			$matches = [];
			preg_match('/"responses\[inputName~index]" value="(\d)"/i', $output, $matches);
			$position = $matches[1];
			
			$this->assertStringContainsString("input$position", $output);
			$this->assertMatchesRegularExpression("/responses\[inputName~index].+value=\"$position\"/i", $output);
			$this->assertEquals($output, $this->getQuestionnaireSaver()->dynamic_input($input, true, 'name123', 'value123', 123, 1234)); //make sure order does not change
			$this->assertArrayNotHasKey($position, $rememberedPositions, "Got position $position twice. History so far: $history$position");
			
			if($position != $lastValue+1)
				$ordered = false;
			$lastValue = $position;
			$history .= "$position,";
			$rememberedPositions[$position] = true;
			if(count($rememberedPositions) >= $subInputCount) {
				$rememberedPositions = [];
				$lastValue = 0;
			}
		}
		
		$this->assertEquals($expectedOrder, $ordered, 'Order was unexpected. History was: ' .substr($history, 0, -1));
	}
	function test_dynamic_input_with_sequential_order() {
		$input = $this->createObj([
			'name' => 'inputName',
			'text' => 'content',
			'subInputs' => [
				['defaultValue' => 'input1'],
				['defaultValue' => 'input2', 'required' => true],
				['defaultValue' => 'input3'],
				['defaultValue' => 'input4']
			]
		]);
		$_COOKIE = [
			'inputName123_saved' => -1
		];
		
		$this->assertOrderOfDynamicInput($input, true);
	}
	function test_dynamic_input_with_random_order() {
		$input = $this->createObj([
			'name' => 'inputName',
			'random' => true,
			'subInputs' => [
				['defaultValue' => 'input1'],
				['defaultValue' => 'input2'],
				['defaultValue' => 'input3'],
				['defaultValue' => 'input4']
			]
		]);
		
		$this->assertOrderOfDynamicInput($input, false);
	}
	
	function test_image() {
		$input = $this->createObj([
			'url' => 'url123',
		]);
		$output = $this->getQuestionnaireSaver()->image($input, false, 'name123', 'value123');
		$this->assertStringContainsStringIgnoringCase('url123', $output);
		$this->assertStringContainsStringIgnoringCase('name123', $output);
	}
	
	function test_likert() {
		$input = $this->createObj([
			'likertSteps' => 3
		]);
		
		$output = $this->getQuestionnaireSaver()->likert($input, false, 'name123', '2');
		$this->assertMatchesRegularExpression('/name="name123".+value="1"/i', $output);
		$this->assertMatchesRegularExpression('/name="name123".+value="2".+checked="checked"/i', $output);
		$this->assertMatchesRegularExpression('/name="name123".+value="3"/i', $output);
		
		$output = $this->getQuestionnaireSaver()->likert($input, true, 'name123', '3');
		$this->assertMatchesRegularExpression('/name="name123".+value="1".+required="required"/i', $output);
		$this->assertMatchesRegularExpression('/name="name123".+value="2".+required="required"/i', $output);
		$this->assertMatchesRegularExpression('/name="name123".+value="3".+required="required".+checked="checked"/i', $output);
	}
	
	function test_list_multiple() {
		$input = $this->createObj([
			'name' => 'inputName',
			'listChoices' => [
				'choice1',
				'choice2',
				'choice3'
			]
		]);
		$output = $this->getQuestionnaireSaver()->list_multiple($input, false, 'name123', 'choice2');
		$this->assertMatchesRegularExpression('/name="name123\[]".+value="choice1"/', $output);
		$this->assertMatchesRegularExpression('/name="name123\[]".+value="choice2".+checked="checked"/', $output);
		$this->assertMatchesRegularExpression('/name="name123\[]".+value="choice3"/', $output);
	}
	
	function test_list_single_as_dropdown() {
		$input = $this->createObj([
			'name' => 'inputName',
			'asDropDown' => true,
			'listChoices' => [
				'choice1',
				'choice2',
				'choice3'
			]
		]);
		$output = $this->getQuestionnaireSaver()->list_single($input, false, 'name123', 'choice2');
		$this->assertStringContainsString('name123', $output);
		$this->assertStringContainsString('<option>choice1', $output);
		$this->assertStringContainsString('selected="selected">choice2', $output);
		$this->assertStringContainsString('<option>choice3', $output);
	}
	
	function test_list_single_not_dropdown() {
		$input = $this->createObj([
			'name' => 'inputName',
			'asDropDown' => false,
			'listChoices' => [
				'choice1',
				'choice2',
				'choice3'
			]
		]);
		$output = $this->getQuestionnaireSaver()->list_single($input, false, 'name123', 'choice2');
		$this->assertStringContainsString('name123', $output);
		$this->assertMatchesRegularExpression('/value="choice1".*\/>/', $output);
		$this->assertStringContainsString('value="choice2" checked', $output);
		$this->assertMatchesRegularExpression('/value="choice3".*\/>/', $output);
	}
	
	function test_number() {
		$input = $this->createObj([
			'name' => 'inputName',
		]);
		$output = $this->getQuestionnaireSaver()->number($input, false, 'name123', '8');
		$this->assertStringContainsString('name="name123"', $output);
		$this->assertStringContainsString('value="8"', $output);
		$this->assertStringContainsString('step="1"', $output);
		$this->assertStringNotContainsString('required="required"', $output);
		
		$output = $this->getQuestionnaireSaver()->number($input, true, 'name123', '8');
		$this->assertStringContainsString('required="required"', $output);
		
		$input = $this->createObj([
			'name' => 'inputName',
			'numberHasDecimal' => true
		]);
		$output = $this->getQuestionnaireSaver()->number($input, false, 'name123', '8');
		$this->assertStringContainsString('step="0.5"', $output);
	}
	
	function test_text_input() {
		$input = $this->createObj([]);
		$output = $this->getQuestionnaireSaver()->text_input($input, false, 'name123', 'value123');
		$this->assertStringContainsString('name="name123"', $output);
		$this->assertStringContainsString('value123', $output);
		$this->assertStringNotContainsString('required="required"', $output);
		
		$output = $this->getQuestionnaireSaver()->text_input($input, true, 'name123', 'value123');
		$this->assertStringContainsString('required="required"', $output);
	}
	
	function test_time() {
		$input = $this->createObj([]);
		$output = $this->getQuestionnaireSaver()->time($input, false, 'name123', '12:45');
		$this->assertStringContainsString('name="name123"', $output);
		$this->assertStringContainsString('value="12:45"', $output);
		$this->assertStringNotContainsString('required="required"', $output);
		
		$output = $this->getQuestionnaireSaver()->time($input, true, 'name123', 'value123');
		$this->assertStringContainsString('required="required"', $output);
	}
	
	function test_va_scale() {
		$input = $this->createObj([]);
		$output = $this->getQuestionnaireSaver()->va_scale($input, false, 'name123', '45');
		$this->assertStringContainsString('name="name123"', $output);
		$this->assertStringContainsString('value="45"', $output);
		$this->assertStringNotContainsString('required="required"', $output);
		
		$output = $this->getQuestionnaireSaver()->va_scale($input, true, 'name123', '');
		$this->assertStringContainsString('value="50"', $output);
		$this->assertStringContainsString('required="required"', $output);
	}
	
	function test_video() {
		$input = $this->createObj([]);
		$output = $this->getQuestionnaireSaver()->video($input, false, 'name123', 'value123');
		$this->assertStringContainsString('name="name123"', $output);
	}
}