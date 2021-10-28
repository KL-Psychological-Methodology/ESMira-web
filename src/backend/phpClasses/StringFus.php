<?php

namespace phpClasses;

require_once DIR_BASE.'/config/configs.php';

class StringFus {
	static function check_input($s) {
		return empty($s) || (strlen($s) < MAX_USERINPUT_LENGTH && preg_match('/^[a-zA-Z0-9À-ž_\-().\s]+$/', $s));
	}
	
	static function strip_input($s) {
		if(strlen($s) > MAX_USERINPUT_LENGTH)
			$s = substr($s, 0, MAX_USERINPUT_LENGTH);
		//it should be ok to save userinput mostly "as is" to the filesystem as long as its not used otherwise:
		return str_replace('"', '\'', $s);
	}
	static function strip_oneLineInput($s) {
		return str_replace(["\n", "\r"], ' ', self::strip_input($s));
	}
	
	static function interpret_inputValue($v) {
		if(is_array($v))
			return implode(',', $v);
		else
			return $v;
	}
}