<?php

namespace test\backend\noJs;

use backend\DataSetCache;
use backend\Main;
use backend\noJs\ForwardingException;
use backend\noJs\InputToString;
use backend\noJs\NoJsMain;
use backend\noJs\pages\StudiesList;
use backend\noJs\StudyData;
use backend\ResponsesIndex;
use backend\subStores\ResponsesStore;
use backend\subStores\StudyAccessIndexStore;
use backend\subStores\StudyStore;
use backend\subStores\UserDataStore;
use PHPUnit\Framework\MockObject\Stub;
use stdClass;
use test\testConfigs\BaseMockedTestSetup;
use test\testConfigs\SkipArgument;

require_once __DIR__ .'/../../../backend/autoload.php';

class InputToStringTest extends BaseMockedTestSetup {
	private $lastCompleted = 123456789;
	private function getInputToOutput(): InputToString {
		return new InputToString(123, $this->lastCompleted, 0, []);
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
		$output = $this->getInputToOutput()->drawInput($input);
		$this->assertStringContainsString('responses[inputName]', $output);
	}
	function test_drawInput_with_default_value() {
		$input = $this->createObj([
			'name' => 'inputName',
			'defaultValue' => 'value123'
		]);
		$output = $this->getInputToOutput()->drawInput($input);
		$this->assertStringContainsString('responses[inputName]', $output);
		$this->assertStringContainsString('value123', $output);
	}
	function test_drawInput_with_non_existing_type() {
		$input = $this->createObj([
			'name' => 'inputName',
			'responseType' => 'not existing'
		]);
		$output = $this->getInputToOutput()->drawInput($input);
		$this->assertStringContainsString('Broken Input', $output);
	}
	function test_drawInput_with_skipped_type() {
		$input = $this->createObj([
			'name' => 'inputName',
			'responseType' => 'app_usage'
		]);
		$output = $this->getInputToOutput()->drawInput($input);
		$this->assertEquals('', $output);
	}
	
	function test_error() {
		$input = $this->createObj([
		]);
		$output = $this->getInputToOutput()->error($input);
		$this->assertStringContainsString('Broken Input', $output);
	}
	
	function test_text() {
		$input = $this->createObj([
			'text' => 'textContent',
		]);
		$output = $this->getInputToOutput()->text($input, false, 'name123', 'value123');
		$this->assertStringContainsString('textContent', $output);
		
		$output = $this->getInputToOutput()->text($input, true, 'name123', 'value123');
		$this->assertStringContainsString('textContent*', $output);
	}
	
	function test_binary() {
		$input = $this->createObj([]);
		$output = $this->getInputToOutput()->binary($input, false, 'name123', '0');
		$this->assertMatchesRegularExpression('/name="name123".+value="0".+checked="checked"/i', $output);
		$this->assertMatchesRegularExpression('/name="name123".+value="1"/i', $output);
		
		$output = $this->getInputToOutput()->binary($input, false, 'name123', '1');
		$this->assertMatchesRegularExpression('/name="name123".+value="0"/i', $output);
		$this->assertMatchesRegularExpression('/name="name123".+value="1".+checked="checked"/i', $output);
		
		$output = $this->getInputToOutput()->binary($input, false, 'name123', '');
		$this->assertStringNotContainsString('checked', $output);
		
		$output = $this->getInputToOutput()->binary($input, true, 'name123', '0');
		$this->assertMatchesRegularExpression('/name="name123".+value="0".+required="required"/i', $output);
		$this->assertMatchesRegularExpression('/name="name123".+value="1".+required="required"/i', $output);
	}
	
	function test_date() {
		$input = $this->createObj([]);
		$output = $this->getInputToOutput()->date($input, false, 'name123', 'value123');
		$this->assertStringContainsStringIgnoringCase('name123', $output);
		$this->assertStringContainsStringIgnoringCase('value123', $output);
		
		$output = $this->getInputToOutput()->date($input, true, 'name123', 'value123');
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
			$_COOKIE['inputName123_saved'] = $this->lastCompleted-1; //simulate filled out questionnaire
			
			$output = $this->getInputToOutput()->dynamic_input($input, true, 'name123', 'value123');
			$matches = [];
			preg_match('/"responses\[inputName~index]" value="(\d)"/i', $output, $matches);
			$position = $matches[1];
			
			$this->assertStringContainsString("input$position", $output);
			$this->assertMatchesRegularExpression("/responses\[inputName~index].+value=\"$position\"/i", $output);
			$this->assertEquals($output, $this->getInputToOutput()->dynamic_input($input, true, 'name123', 'value123')); //make sure order does not change
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
		$output = $this->getInputToOutput()->image($input, false, 'name123', 'value123');
		$this->assertStringContainsStringIgnoringCase('url123', $output);
		$this->assertStringContainsStringIgnoringCase('name123', $output);
	}
	
	function test_likert() {
		$input = $this->createObj([
			'likertSteps' => 3
		]);
		
		$output = $this->getInputToOutput()->likert($input, false, 'name123', '2');
		$this->assertMatchesRegularExpression('/name="name123".+value="1"/i', $output);
		$this->assertMatchesRegularExpression('/name="name123".+value="2".+checked="checked"/i', $output);
		$this->assertMatchesRegularExpression('/name="name123".+value="3"/i', $output);
		
		$output = $this->getInputToOutput()->likert($input, true, 'name123', '3');
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
		$output = $this->getInputToOutput()->list_multiple($input, false, 'name123', 'choice2');
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
		$output = $this->getInputToOutput()->list_single($input, false, 'name123', 'choice2');
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
		$output = $this->getInputToOutput()->list_single($input, false, 'name123', 'choice2');
		$this->assertStringContainsString('name123', $output);
		$this->assertMatchesRegularExpression('/value="choice1".*\/>/', $output);
		$this->assertStringContainsString('value="choice2" checked', $output);
		$this->assertMatchesRegularExpression('/value="choice3".*\/>/', $output);
	}
	
	function test_number() {
		$input = $this->createObj([
			'name' => 'inputName',
		]);
		$output = $this->getInputToOutput()->number($input, false, 'name123', '8');
		$this->assertStringContainsString('name="name123"', $output);
		$this->assertStringContainsString('value="8"', $output);
		$this->assertStringContainsString('step="1"', $output);
		$this->assertStringNotContainsString('required="required"', $output);
		
		$output = $this->getInputToOutput()->number($input, true, 'name123', '8');
		$this->assertStringContainsString('required="required"', $output);
		
		$input = $this->createObj([
			'name' => 'inputName',
			'numberHasDecimal' => true
		]);
		$output = $this->getInputToOutput()->number($input, false, 'name123', '8');
		$this->assertStringContainsString('step="0.5"', $output);
	}
	
	function test_text_input() {
		$input = $this->createObj([]);
		$output = $this->getInputToOutput()->text_input($input, false, 'name123', 'value123');
		$this->assertStringContainsString('name="name123"', $output);
		$this->assertStringContainsString('value123', $output);
		$this->assertStringNotContainsString('required="required"', $output);
		
		$output = $this->getInputToOutput()->text_input($input, true, 'name123', 'value123');
		$this->assertStringContainsString('required="required"', $output);
	}
	
	function test_time() {
		$input = $this->createObj([]);
		$output = $this->getInputToOutput()->time($input, false, 'name123', '12:45');
		$this->assertStringContainsString('name="name123"', $output);
		$this->assertStringContainsString('value="12:45"', $output);
		$this->assertStringNotContainsString('required="required"', $output);
		
		$output = $this->getInputToOutput()->time($input, true, 'name123', 'value123');
		$this->assertStringContainsString('required="required"', $output);
	}
	
	function test_va_scale() {
		$input = $this->createObj([]);
		$output = $this->getInputToOutput()->va_scale($input, false, 'name123', '45');
		$this->assertStringContainsString('name="name123"', $output);
		$this->assertStringContainsString('value="45"', $output);
		$this->assertStringNotContainsString('required="required"', $output);
		
		$output = $this->getInputToOutput()->va_scale($input, true, 'name123', '');
		$this->assertStringContainsString('value="50"', $output);
		$this->assertStringContainsString('required="required"', $output);
	}
	
	function test_video() {
		$input = $this->createObj([]);
		$output = $this->getInputToOutput()->video($input, false, 'name123', 'value123');
		$this->assertStringContainsString('name="name123"', $output);
	}
}