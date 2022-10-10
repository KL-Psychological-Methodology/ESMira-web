<?php

namespace backend;


class JsonOutput {
	private static function doHeaders() {
		Main::setHeader('Content-Type: application/json;charset=UTF-8');
		Main::setHeader('Cache-Control: no-cache, must-revalidate');
	}
	static function error(string $string, int $errorCode = 0): string {
		self::doHeaders();
		return json_encode(['success' => false, 'serverVersion' => Main::SERVER_VERSION, 'error' => $string, 'errorCode' => $errorCode]);
	}
	
	static function successString(string $s = '1'): string {
		self::doHeaders();
		return '{"success":true,"serverVersion":'.Main::SERVER_VERSION.',"dataset":'.$s.'}';
	}
	
	static function successObj(/*mixed*/ $obj = true): string {
		self::doHeaders();
		return json_encode(['success' => true, 'serverVersion' => Main::SERVER_VERSION, 'dataset' => $obj]);
	}
}