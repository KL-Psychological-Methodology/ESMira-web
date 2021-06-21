<?php
require_once 'configs.php';
require_once 'basic_fu.php';

function error($s) {
	exit('{"success":false,"serverVersion":'.SERVER_VERSION.',"error":"'.$s.'"}');
}


function success($s=1) {
	exit('{"success":true,"serverVersion":'.SERVER_VERSION.',"dataset":'.$s.'}');
}


?>