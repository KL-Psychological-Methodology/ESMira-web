<?php
define('DIR_BASE', dirname(dirname(__FILE__)) .'/');
function autoloader($class) {
	$class = str_replace('\\', '/', $class);
	include DIR_BASE . "$class.php";
}
spl_autoload_register('autoloader');