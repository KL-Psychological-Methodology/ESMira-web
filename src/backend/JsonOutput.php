<?php

namespace backend;


class JsonOutput {
	private static function doHeaders() {
		Main::setHeader('Content-Type: application/json;charset=UTF-8');
		Main::setHeader('Cache-Control: no-cache, must-revalidate');
	}
	static function error($string): string {
		self::doHeaders();
		return json_encode(['success' => false, 'serverVersion' => Main::SERVER_VERSION, 'error' => $string]);
	}
	
	static function successString($s = 1) {
		self::doHeaders();
		return '{"success":true,"serverVersion":'.Main::SERVER_VERSION.',"dataset":'.$s.'}';
	}
	
	static function successObj($obj = true) {
		self::doHeaders();
		return json_encode(['success' => true, 'serverVersion' => Main::SERVER_VERSION, 'dataset' => $obj]);
	}
}