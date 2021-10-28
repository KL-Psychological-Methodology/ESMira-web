<?php
define('DIR_BASE', dirname(dirname(dirname(__FILE__))) .'/');
function autoloader($class) {
	include DIR_BASE . "$class.php";
}
spl_autoload_register('autoloader');