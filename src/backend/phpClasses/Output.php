<?php

namespace phpClasses;

use phpClasses\Base;

class Output {
	static function error($s) {
		exit('{"success":false,"serverVersion":'.Base::SERVER_VERSION.',"error":"'.$s.'"}');
	}
	
	static function success($s = '') {
		exit('{"success":true,"serverVersion":'.Base::SERVER_VERSION.',"dataset":'.$s.'}');
	}
}