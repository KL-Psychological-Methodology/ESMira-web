<?php

namespace backend;


class Output {
	static function error($string) {
		header('Content-Type: application/json;charset=UTF-8');
		header('Cache-Control: no-cache, must-revalidate');
		exit(json_encode(['success' => false, 'serverVersion' => Base::SERVER_VERSION, 'error' => $string]));
	}
	
	static function successString($s = 1) {
		header('Content-Type: application/json;charset=UTF-8');
		header('Cache-Control: no-cache, must-revalidate');
		exit('{"success":true,"serverVersion":'.Base::SERVER_VERSION.',"dataset":'.$s.'}');
	}
	
	static function successObj($obj = true) {
		header('Content-Type: application/json;charset=UTF-8');
		header('Cache-Control: no-cache, must-revalidate');
		exit(json_encode(['success' => true, 'serverVersion' => Base::SERVER_VERSION, 'dataset' => $obj]));
	}
}