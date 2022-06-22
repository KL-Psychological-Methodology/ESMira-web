<?php

namespace backend\noJs;

use backend\Main;
use stdClass;

class InputToString {
	/**
	 * @var int
	 */
	var $studyId; //only used for dynamic input
	/**
	 * @var int
	 */
	var $lastCompleted; //only used for dynamic input
	/**
	 * @var int
	 */
	var $pageIndex;
	/**
	 * @var array
	 */
	var $cache;
	
	function __construct(int $study_id, int $last_completed, int $pageIndex, array $cache) {
		$this->studyId = $study_id;
		$this->lastCompleted = $last_completed;
		$this->pageIndex = $pageIndex;
		$this->cache = $cache;
	}
	
	function drawInput(stdClass &$input): string {
		$name = 'responses['.$input->name.']';
		$value = (isset($this->cache[$input->name])) ? $this->cache[$input->name] : ($input->defaultValue ?? '');
		$required = $input->required ?? false;
		
		$responseType = $input->responseType ?? 'text_input';
		
		if($this->isSkipped($responseType))
			return '';
		else if(!method_exists($this, $responseType))
			$output = $this->error($input);
		else
			$output = $this->{$responseType}($input, $required, $name, $value);
		
		return "<div class=\"line horizontalPadding verticalPadding\">$output</div>";
	}
	
	function isSkipped($responseType): bool {
		switch($responseType) {
			case "app_usage":
			case "photo":
				return true;
			default:
				return false;
		}
	}
	
	
	function error(stdClass $input): string {
		$responseType = $input->responseType ?? 'Error';
		return "<div class=\"highlight center\">Broken Input ($responseType)</div>";
	}
	
	function text(stdClass $input, bool $required, string $name, string $value): string {
		$text = isset($input->text) ? $input->text : '';
		$output = $text;
		if($required && strlen($text))
			$output .= '*';
		return "$output<br/>";
	}
	
	function binary(stdClass $input, bool $required, string $name, string $value): string {
		$leftSideLabel = $input->leftSideLabel ?? '';
		$rightSideLabel = $input->rightSideLabel ?? '';
		if($value == '0') {
			$leftChecked = 'checked="checked"';
			$rightChecked = '';
		}
		else if($value == '1') {
			$leftChecked = '';
			$rightChecked = 'checked="checked"';
		}
		else {
			$leftChecked = '';
			$rightChecked = '';
		}
		$requiredMarker = $required ? 'required="required"' : '';
		
		return $this->text($input, $required, $name, $value)
			."<div class=\"list_parent\">
				<div class=\"list_child center\">&nbsp;
					<label class=\"left no_desc no_title\">
						$leftSideLabel
						<input type=\"radio\" name=\"$name\" value=\"0\" $leftChecked $requiredMarker/>
					</label>
					<label class=\"right no_desc no_title\">
						<input type=\"radio\" name=\"$name\" value=\"1\" $rightChecked $requiredMarker/>
						$rightSideLabel
					</label>
				</div>
			</div>";
	}
	
	function date(stdClass $input, bool $required, string $name, string $value): string {
		$requiredMarker = $required ? 'required="required"' : '';
		return $this->text($input, $required, $name, $value)
			."<div class=\"center\"><input name=\"$name\" value=\"$value\" type=\"date\" $requiredMarker/></div>";
	}
	
	function dynamic_input(stdClass $input, bool $required, string $name, string $value): string {
		$cookieBase = ($input->name ?? 'name') .$this->studyId;
		$dynamicIndex = $_COOKIE[$cookieBase . '_current'] ?? -1;
		$choices = $input->subInputs ?? [];
		
		if(!isset($_COOKIE[$cookieBase.'_saved']) || $_COOKIE[$cookieBase.'_saved'] <= $this->lastCompleted || $dynamicIndex < 0 || $dynamicIndex >= sizeof($choices)) {
			if(isset($input->random) && $input->random) {
				$cookieCompleted = $cookieBase .'_completed';
				
				if($dynamicIndex >= 0) {
					Main::setCookie($cookieCompleted .$dynamicIndex, '1');
					$_COOKIE[$cookieCompleted .$dynamicIndex] = '1';
				}
				
				$choicesLeft = [];
				for($i = sizeof($choices) - 1; $i >= 0; --$i) {
					if(!isset($_COOKIE[$cookieCompleted .$i]) || !$_COOKIE[$cookieCompleted .$i])
						$choicesLeft[] = $i;
				}
				if(!sizeof($choicesLeft)) {
					for($i = sizeof($choices) - 1; $i >= 0; --$i) {
						$choicesLeft[] = $i;
						Main::deleteCookie($cookieCompleted .$i);
					}
				}
				$dynamicIndex = $choicesLeft[rand(0, sizeof($choicesLeft) - 1)];
			}
			else if($dynamicIndex < 0 || ++$dynamicIndex >= sizeof($choices))
				$dynamicIndex = 0;
			
			Main::setCookie($cookieBase.'_current', $dynamicIndex);
			Main::setCookie($cookieBase.'_saved', time());
		}
		
		if(!isset($choices[$dynamicIndex]))
			return $this->error($input);
		
		$element = clone $choices[$dynamicIndex];
		
		if(isset($element->required)) {
			$required = $element->required;
			if($element->required && isset($input->text) && strlen($input->text)) {
				$element->required = false; //we dont want to display the required marker twice
			}
		}
		$element->name = $input->name;
		
		return $this->text($input, $required, $name, $value)
			.'<div>
				<div>'. $this->drawInput($element) .'</div>
				<input type="hidden" name="responses['.$input->name."~index]" .'" value="'.($dynamicIndex+1).'"/>
			</div>';
	}
	
	function image(stdClass $input, bool $required, string $name, string $value): string {
		$url = $input->url ?? '';
		return $this->text($input, $required, $name, $value)
			."<div class=\"center\"><img alt=\"\" src=\"$url\"/><input type=\"hidden\" name=\"$name\" value=\"1\"/></div>";
	}
	
	function likert(stdClass $input, bool $required, string $name, string $value): string {
		$leftSideLabel = $input->leftSideLabel ?? '';
		$rightSideLabel = $input->rightSideLabel ?? '';
		$requiredMarker = $required ? 'required="required"' : '';
		
		$radioBoxes = '';
		for($i=1, $max=isset($input->likertSteps) ? $input->likertSteps+1 : 6; $i<$max; ++$i) {
			$radioBoxes .= "<input type=\"radio\" name=\"$name\" value=\"$i\" $requiredMarker ".($value==$i ? 'checked="checked"' : '') .'/>';
		}
		
		return $this->text($input, $required, $name, $value)
			."<div class=\"center\">
			<div>&nbsp;
				<div class=\"left small_text\">$leftSideLabel</div>
				<div class=\"right small_text\">$rightSideLabel</div>
			</div>
			<div>
				$radioBoxes
			</div>
		</div>";
	}
	
	function list_multiple(stdClass $input, bool $required, string $name, string $value): string {
		$radioBoxes = '';
		foreach($input->listChoices ?? [] as $v) {
			$radioBoxes .= '<label class="no_title no_desc vertical"><input class="horizontal" type="checkbox" name="'.$name.'[]" value="'.$v.'" ' .(strpos($value, $v) !== false ? 'checked="checked"' : '') .'/><span>'.$v.'</span></label>';
		}
		return $this->text($input, $required, $name, $value)
			."<div class=\"list_parent\">
			<div class=\"list_child\">
				$radioBoxes
			</div>
		</div>";
	}
	
	function list_single(stdClass $input, bool $required, string $name, string $value): string {
		$output = $this->text($input, $required, $name, $value);
		$requiredMarker = $required ? 'required="required"' : '';
		
		if(isset($input->asDropDown) && !$input->asDropDown) {
			$output .= '<div class="list_parent"><div class="list_child center">';
			foreach($input->listChoices as $choiceValue) {
				$output .= "<label class=\"vertical no_desc no_title\"><input type=\"radio\" name=\"$name\" value=\"$choiceValue\" ".($choiceValue==$value ? 'checked="checked"' : '') ."$requiredMarker/>
					<span>$choiceValue</span>
					</label>";
			}
			$output .= '</div></div>';
		}
		else {
			//optgroup is added as a workaround for IOS: https://stackoverflow.com/questions/19011978/ios-7-doesnt-show-more-than-one-line-when-option-is-longer-than-screen-size
			
			$output .= "<div class=\"center\"><select name=\"$name\" $requiredMarker\"><option value=\"\">" .Lang::get('please_select') .'</option>';
			foreach ($input->listChoices ?? [] as $v) {
				$output .= '<option' .($value == $v ? ' selected="selected"' : '') .">$v</option>";
			}
			$output .= '<optgroup label=""></optgroup></select></div>';
		}
		
		return $output;
	}
	function number(stdClass $input, bool $required, string $name, string $value): string {
		$step = isset($input->numberHasDecimal) && $input->numberHasDecimal ? '0.5' : '1';
		$requiredMarker = $required ? 'required="required"' : '';
		
		return $this->text($input, $required, $name, $value)
			."<div class=\"center\"><input name=\"$name\" value=\"$value\" type=\"number\" step=\"$step\" style=\"width: 100px;\" $requiredMarker/></div>";
	}
	
	function text_input(stdClass $input, bool $required, string $name, string $value): string {
		$requiredMarker = $required ? 'required="required"' : '';
		return $this->text($input, $required, $name, $value)
			."<div class=\"center\"><textarea name=\"$name\" $requiredMarker>$value</textarea></div>";
	}
	
	function time(stdClass $input, bool $required, string $name, string $value): string {
		$requiredMarker = $required ? 'required="required"' : '';
		return $this->text($input, $required, $name, $value)
			."<div class=\"center\"><input name=\"$name\" value=\"$value\" type=\"time\" $requiredMarker/></div>";
	}
	
	function va_scale(stdClass $input, bool $required, string $name, string $value): string {
		$leftSideLabel = $input->leftSideLabel ?? '';
		$rightSideLabel = $input->rightSideLabel ?? '';
		$requiredMarker = $required ? 'required="required"' : '';
		return $this->text($input, $required, $name, $value)
			."<div class=\"center\">&nbsp;
			<div class=\"left small_text\">$leftSideLabel</div>
			<div class=\"right small_text\">$rightSideLabel</div>
		</div>
		<div class=\"center\">
			<input name=\"$name\" value=\"" .($value != "" ? $value : 50) ."\" type=\"range\" min=\"0\" max=\"100\" $requiredMarker/>
		</div>";
	}
	
	function video(stdClass $input, bool $required, string $name, string $value): string {
		$url = $input->url ?? '';
		return $this->text($input, $required, $name, $value)
			."<div class=\"center\"><iframe src=\"$url\"></iframe></div><input type=\"hidden\" name=\"$name\" value=\"1\"/>";
	}
}