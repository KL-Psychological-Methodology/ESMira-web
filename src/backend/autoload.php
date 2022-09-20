<?php
define('DIR_BASE', dirname(__FILE__, 2) .'/');
spl_autoload_register(function($class) {
	$class = str_replace('\\', '/', $class);
	if(file_exists(DIR_BASE . "$class.php"))
		include DIR_BASE . "$class.php";
});