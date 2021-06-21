<?php
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
		echo '<div class="line horizontalPadding verticalPadding">';
		$name = 'responses['.$this->pageIndex.'][' .$inputIndex .']';
		$cache = $this->cache;
		$value = (isset($cache[$input->name])) ? $cache[$input->name] : (isset($input->defaultValue) ? $input->defaultValue : '');
		$required = isset($input->required) ? $input->required : false;
		
		$responseType = isset($input->responseType) ? $input->responseType : 'text_input';
		
		if(!method_exists($this, $responseType))
			$this->error($input);
		else
			$this->{$responseType}($input, $required, $name, $value, $inputIndex);
		
		echo '</div>';
	}
	
	
	function error($input) {
		$responseType = isset($input->responseType) ? $input->responseType : 'Error';
		echo "<div class=\"highlight\">Broken Input ($responseType)</div>";
	}
	
	function text($input, $required, $name, $value, $index) {
		$text = isset($input->text) ? $input->text : '';
		echo $text;
		if($required && strlen($text))
			echo '*';
		echo '<br/>';
	}
	
	function binary($input, $required, $name, $value, $index) {
		$this->text($input, $required, $name, $value, $index);
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
		
		echo "<div class=\"list_child\">
			<div>&nbsp;
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
		$this->text($input, $required, $name, $value, $index);
		$requiredMarker = $required ? 'required="required"' : '';
		echo "<input name=\"$name\" value=\"$value\" type=\"date\" $requiredMarker/>";
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
					create_cookie($cookie_completed .$dynamicIndex, '1', 32532447600);
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
						create_cookie($cookie_completed .$i, '0', time() - 3600);
					}
				}
				$dynamicIndex = $choices_left[rand(0, sizeof($choices_left) - 1)];
			}
			else {
				if($dynamicIndex === -1 || ++$dynamicIndex >= sizeof($choices))
					$dynamicIndex = 0;
			}
			
			create_cookie($cookie_base.'_current', $dynamicIndex, 32532447600);
			create_cookie($cookie_base.'_saved', time(), 32532447600);
		}
		
		if(!isset($choices[$dynamicIndex]))
			$this->error($input);
		else {
			$element = $choices[$dynamicIndex];
			
			if(isset($element->required)) {
				$required = $element->required;
				if($element->required && isset($input->text) && strlen($input->text))
					$element->required = false;
			}
			if(isset($element->name))
				$element->name = $input->name;
			
			$this->text($input, $required, $name, $value, $index);
			echo '<div>
				<div>'. $this->draw_input($element, $index) .'</div>
				<input name="concat_values['.$this->pageIndex."][$index]" .'" type="hidden" value="'.($dynamicIndex+1).'"/>
			</div>';
		}
	}
	
	function image($input, $required, $name, $value, $index) {
		$this->text($input, $required, $name, $value, $index);
		$url = isset($input->url) ? $input->url : '';
		echo "<img alt=\"\" height=\"250\" src=\"$url\"/><input type=\"hidden\" name=\"$name\" value=\"1\"/>";
	}
	
	function likert($input, $required, $name, $value, $index) {
		$this->text($input, $required, $name, $value, $index);
		$leftSideLabel = isset($input->leftSideLabel) ? $input->leftSideLabel : '';
		$rightSideLabel = isset($input->rightSideLabel) ? $input->rightSideLabel : '';
		$requiredMarker = $required ? 'required="required"' : '';
		
		$radioBoxes = '';
		for($i=1, $max=isset($input->likertSteps) ? $input->likertSteps+1 : 6; $i<$max; ++$i) {
			$radioBoxes .= "<input type=\"radio\" name=\"$name\" value=\"$i\" $requiredMarker ".($value==$i ? 'checked="checked"' : '') .'/>';
		}
		
		echo "<div class=\"list_child\">
			<div>&nbsp;
				<div class=\"left small_text\">$leftSideLabel</div>
				<div class=\"right small_text\">$rightSideLabel</div>
			</div>
			<div style=\"text-align: center;\">
				$radioBoxes
			</div>
		</div>";
	}
	
	function list_multiple($input, $required, $name, $value, $index) {
		$this->text($input, $required, $name, $value, $index);
		$radioBoxes = '';
		foreach($input->listChoices as $v) {
			$radioBoxes .= '<label class="no_title no_desc vertical"><input class="horizontal" type="checkbox" name="'.$name.'[]" value="'.$v.'" ' .(strpos($value, $v) ? 'checked="checked"' : '') .'/><span>'.$v.'</span></label>';
		}
		echo "<div class=\"list_parent\">
			<div class=\"list_child\">
				$radioBoxes
			</div>
		</div>";
	}
	
	function list_single($input, $required, $name, $value, $index) {
		global $LANG;
		$this->text($input, $required, $name, $value, $index);
		$requiredMarker = $required ? 'required="required"' : '';
		
		if(isset($input->asDropDown) && !$input->asDropDown) {
			echo '<div class="list_child" style="text-align: center;">';
			foreach($input->listChoices as $choiceValue) {
				echo "<label class=\"vertical no_desc no_title\"><input type=\"radio\" name=\"$name\" value=\"$choiceValue\" ".($choiceValue==$value ? 'checked="checked"' : '') ."$requiredMarker/>
					<span>$choiceValue</span>
					</label>";
			}
			echo '</div>';
		}
		else {
			//optgroup is added as a workaround for IOS: https://stackoverflow.com/questions/19011978/ios-7-doesnt-show-more-than-one-line-when-option-is-longer-than-screen-size
			
			echo "<select name=\"$name\" $requiredMarker\"><option value=\"\">" .$LANG->please_select .'</option>';
			foreach ($input->listChoices as $v) {
				echo '<option' .($name == $v ? ' selected="selected"' : '') .">$v</option>";
			}
			echo '<optgroup label=""></optgroup></select>';
		}
	}
	function number($input, $required, $name, $value, $index) {
		$this->text($input, $required, $name, $value, $index);
		$step = isset($input->numberHasDecimal) && $input->numberHasDecimal ? '0.5' : '1';
		$requiredMarker = $required ? 'required="required"' : '';
		
		echo "<input name=\"$name\" value=\"$value\" type=\"number\" step=\"$step\" style=\"width: 100px;\" $requiredMarker/>";
	}
	
	function text_input($input, $required, $name, $value, $index) {
		$this->text($input, $required, $name, $value, $index);
		$requiredMarker = $required ? 'required="required"' : '';
		echo "<textarea name=\"$name\" $requiredMarker>$value</textarea>";
	}
	
	function time($input, $required, $name, $value, $index) {
		$this->text($input, $required, $name, $value, $index);
		$requiredMarker = $required ? 'required="required"' : '';
		echo "<input name=\"$name\" value=\"$value\" type=\"time\" $requiredMarker/>";
	}
	
	function time_old($input, $required, $name, $value, $index) {
		$this->text($input, $required, $name, $value, $index);
		$requiredMarker = $required ? 'required="required"' : '';
		echo "<input name=\"$name\" value=\"$value\" type=\"time\" $requiredMarker/>";
	}
	
	function va_scale($input, $required, $name, $value, $index) {
		$this->text($input, $required, $name, $value, $index);
		$leftSideLabel = isset($input->leftSideLabel) ? $input->leftSideLabel : '';
		$rightSideLabel = isset($input->rightSideLabel) ? $input->rightSideLabel : '';
		$requiredMarker = $required ? 'required="required"' : '';
		echo "<div class=\"vas_label\">&nbsp;
			<div class=\"left small_text\">$leftSideLabel</div>
			<div class=\"right small_text\">$rightSideLabel</div>
		</div>
		<input name=\"$name\" value=\"" .($value != "" ? $value : 50) ."\" type=\"range\" min=\"0\" max=\"100\" $requiredMarker/>";
	}
	
	function video($input, $required, $name, $value, $index) {
		$this->text($input, $required, $name, $value, $index);
		$url = isset($input->url) ? $input->url : '';
		echo "<iframe height=\"250\" src=\"$url\"></iframe><input type=\"hidden\" name=\"$name\" value=\"1\"/>";
	}
}
?>