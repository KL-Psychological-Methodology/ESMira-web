<?php

namespace backend;

use backend\Base;

class Output {
	static function error($s) {
		header('Content-Type: application/json;charset=UTF-8');
		header('Cache-Control: no-cache, must-revalidate');
		exit('{"success":false,"serverVersion":'.Base::SERVER_VERSION.',"error":"'.$s.'"}');
	}
	
	static function success($s = 1) {
		header('Content-Type: application/json;charset=UTF-8');
		header('Cache-Control: no-cache, must-revalidate');
		exit('{"success":true,"serverVersion":'.Base::SERVER_VERSION.',"dataset":'.$s.'}');
	}
}