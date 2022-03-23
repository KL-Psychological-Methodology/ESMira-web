<?php

namespace backend\noJs;

use backend\Base;

class Inputs {
	var $study_id; //only used for dynamic input
	var $last_completed; //only used for dynamic input
	var $pageIndex;
	var $cache;
	
	function __construct($study_id, $last_completed, $pageIndex, $cache) {
		$this->study_id = $study_id;
		$this->last_completed = $last_completed;
		$this->pageIndex = $pageIndex;
		$this->cache = $cache;
	}
	
	function draw_input(&$input, $inputIndex) {
		$name = 'responses['.$this->pageIndex.'][' .$inputIndex .']';
		$cache = $this->cache;
		$value = (isset($cache[$input->name])) ? $cache[$input->name] : (isset($input->defaultValue) ? $input->defaultValue : '');
		$required = isset($input->required) ? $input->required : false;
		
		$responseType = isset($input->responseType) ? $input->responseType : 'text_input';
		
		if($this->is_skipped($responseType)) {
			return '';
		}
		else if(!method_exists($this, $responseType))
			$output = $this->error($input);
		else
			$output = $this->{$responseType}($input, $required, $name, $value, $inputIndex);
		
		return "<div class=\"line horizontalPadding verticalPadding\">$output</div>";
	}
	
	function is_skipped($responseType) {
		switch($responseType) {
			case "app_usage":
			case "photo":
				return true;
		}
		return false;
	}
	
	
	function error($input) {
		$responseType = isset($input->responseType) ? $input->responseType : 'Error';
		return "<div class=\"highlight center\">Broken Input ($responseType)</div>";
	}
	
	function text($input, $required, $name, $value, $index) {
		$text = isset($input->text) ? $input->text : '';
		$output = $text;
		if($required && strlen($text))
			$output .= '*';
		return "$output<br/>";
	}
	
	function binary($input, $required, $name, $value, $index) {
		$leftSideLabel = isset($input->leftSideLabel) ? $input->leftSideLabel : '';
		$rightSideLabel = isset($input->rightSideLabel) ? $input->rightSideLabel : '';
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
		
		return $this->text($input, $required, $name, $value, $index)
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
	
	function date($input, $required, $name, $value, $index) {
		$requiredMarker = $required ? 'required="required"' : '';
		return $this->text($input, $required, $name, $value, $index)
			."<div class=\"center\"><input name=\"$name\" value=\"$value\" type=\"date\" $requiredMarker/></div>";
	}
	
	function dynamic_input($input, $required, $name, $value, $index) {
		$study_id = $this->study_id;
		$last_completed = $this->last_completed;
		
		$cookie_base = (isset($input->name) ? $input->name : 'name').$study_id;
		$dynamicIndex = isset($_COOKIE[$cookie_base.'_current']) ? $_COOKIE[$cookie_base.'_current'] : -1;
		$choices = isset($input->subInputs) ? $input->subInputs : [];
		
		if(!isset($_COOKIE[$cookie_base.'_saved']) || $_COOKIE[$cookie_base.'_saved'] <= $last_completed || $dynamicIndex === -1 || $dynamicIndex >= sizeof($choices)) {
			if(isset($input->random) && $input->random) {
				$cookie_completed = $cookie_base .'_completed';
				
				if($dynamicIndex !== -1) {
					Base::create_cookie($cookie_completed .$dynamicIndex, '1', 32532447600);
					$_COOKIE[$cookie_completed .$dynamicIndex] = '1';
				}
				
				$choices_left = [];
				for($i = sizeof($choices) - 1; $i >= 0; --$i) {
					if(!isset($_COOKIE[$cookie_completed .$i]) || !$_COOKIE[$cookie_completed .$i])
						array_push($choices_left, $i);
				}
				if(!sizeof($choices_left)) {
					for($i = sizeof($choices) - 1; $i >= 0; --$i) {
						array_push($choices_left, $i);
						Base::delete_cookie($cookie_completed .$i);
					}
				}
				$dynamicIndex = $choices_left[rand(0, sizeof($choices_left) - 1)];
			}
			else {
				if($dynamicIndex === -1 || ++$dynamicIndex >= sizeof($choices))
					$dynamicIndex = 0;
			}
			
			Base::create_cookie($cookie_base.'_current', $dynamicIndex, 32532447600);
			Base::create_cookie($cookie_base.'_saved', time(), 32532447600);
		}
		
		if(!isset($choices[$dynamicIndex]))
			return $this->error($input);
		else {
			$element = $choices[$dynamicIndex];
			
			if(isset($element->required)) {
				$required = $element->required;
				if($element->required && isset($input->text) && strlen($input->text))
					$element->required = false;
			}
			if(isset($element->name))
				$element->name = $input->name;
			
			return $this->text($input, $required, $name, $value, $index)
				.'<div>
					<div>'. $this->draw_input($element, $index) .'</div>
					<input name="concat_values['.$this->pageIndex."][$index]" .'" type="hidden" value="'.($dynamicIndex+1).'"/>
				</div>';
		}
	}
	
	function image($input, $required, $name, $value, $index) {
		$url = isset($input->url) ? $input->url : '';
		return $this->text($input, $required, $name, $value, $index)
			."<div class=\"center\"><img alt=\"\" src=\"$url\"/><input type=\"hidden\" name=\"$name\" value=\"1\"/></div>";
	}
	
	function likert($input, $required, $name, $value, $index) {
		$leftSideLabel = isset($input->leftSideLabel) ? $input->leftSideLabel : '';
		$rightSideLabel = isset($input->rightSideLabel) ? $input->rightSideLabel : '';
		$requiredMarker = $required ? 'required="required"' : '';
		
		$radioBoxes = '';
		for($i=1, $max=isset($input->likertSteps) ? $input->likertSteps+1 : 6; $i<$max; ++$i) {
			$radioBoxes .= "<input type=\"radio\" name=\"$name\" value=\"$i\" $requiredMarker ".($value==$i ? 'checked="checked"' : '') .'/>';
		}
		
		return $this->text($input, $required, $name, $value, $index)
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
	
	function list_multiple($input, $required, $name, $value, $index) {
		$radioBoxes = '';
		foreach($input->listChoices as $v) {
			$radioBoxes .= '<label class="no_title no_desc vertical"><input class="horizontal" type="checkbox" name="'.$name.'[]" value="'.$v.'" ' .(strpos($value, $v) ? 'checked="checked"' : '') .'/><span>'.$v.'</span></label>';
		}
		return $this->text($input, $required, $name, $value, $index)
			."<div class=\"list_parent\">
			<div class=\"list_child\">
				$radioBoxes
			</div>
		</div>";
	}
	
	function list_single($input, $required, $name, $value, $index) {
		$output = $this->text($input, $required, $name, $value, $index);
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
			foreach ($input->listChoices as $v) {
				$output .= '<option' .($name == $v ? ' selected="selected"' : '') .">$v</option>";
			}
			$output .= '<optgroup label=""></optgroup></select></div>';
		}
		
		return $output;
	}
	function number($input, $required, $name, $value, $index) {
		$step = isset($input->numberHasDecimal) && $input->numberHasDecimal ? '0.5' : '1';
		$requiredMarker = $required ? 'required="required"' : '';
		
		return $this->text($input, $required, $name, $value, $index)
			."<div class=\"center\"><input name=\"$name\" value=\"$value\" type=\"number\" step=\"$step\" style=\"width: 100px;\" $requiredMarker/></div>";
	}
	
	function text_input($input, $required, $name, $value, $index) {
		$requiredMarker = $required ? 'required="required"' : '';
		return $this->text($input, $required, $name, $value, $index)
			."<div class=\"center\"><textarea name=\"$name\" $requiredMarker>$value</textarea></div>";
	}
	
	function time($input, $required, $name, $value, $index) {
		$requiredMarker = $required ? 'required="required"' : '';
		return $this->text($input, $required, $name, $value, $index)
			."<div class=\"center\"><input name=\"$name\" value=\"$value\" type=\"time\" $requiredMarker/></div>";
	}
	
	function va_scale($input, $required, $name, $value, $index) {
		$leftSideLabel = isset($input->leftSideLabel) ? $input->leftSideLabel : '';
		$rightSideLabel = isset($input->rightSideLabel) ? $input->rightSideLabel : '';
		$requiredMarker = $required ? 'required="required"' : '';
		return $this->text($input, $required, $name, $value, $index)
			."<div class=\"center\">&nbsp;
			<div class=\"left small_text\">$leftSideLabel</div>
			<div class=\"right small_text\">$rightSideLabel</div>
		</div>
		<div class=\"center\">
			<input name=\"$name\" value=\"" .($value != "" ? $value : 50) ."\" type=\"range\" min=\"0\" max=\"100\" $requiredMarker/>
		</div>";
	}
	
	function video($input, $required, $name, $value, $index) {
		$url = isset($input->url) ? $input->url : '';
		return $this->text($input, $required, $name, $value, $index)
			."<div class=\"center\"><iframe src=\"$url\"></iframe></div><input type=\"hidden\" name=\"$name\" value=\"1\"/>";
	}
}