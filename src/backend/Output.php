<?php

namespace backend;

/**
 * @deprecated
 * this is legacy code to fix update from version < 1.5.1
 */
class Output {
	static function error($string) {
		header('Content-Type: application/json;charset=UTF-8');
		header('Cache-Control: no-cache, must-revalidate');
		exit(json_encode(['success' => false, 'serverVersion' => Main::SERVER_VERSION, 'error' => $string]));
	}
	
	static function successString($s = 1) {
		header('Content-Type: application/json;charset=UTF-8');
		header('Cache-Control: no-cache, must-revalidate');
		exit('{"success":true,"serverVersion":'.Main::SERVER_VERSION.',"dataset":'.$s.'}');
	}
	
	static function successObj($obj = true) {
		header('Content-Type: application/json;charset=UTF-8');
		header('Cache-Control: no-cache, must-revalidate');
		exit(json_encode(['success' => true, 'serverVersion' => Main::SERVER_VERSION, 'dataset' => $obj]));
	}
}