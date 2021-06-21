<?php
require_once 'configs.php';
require_once 'basic_fu.php';

function error($s) {
	global $error;
	$error = $s;
	return false;
}

function success($s=1) {
	return true;
}

?>