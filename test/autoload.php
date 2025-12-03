<?php
require_once dirname(__FILE__, 2) .'/src/backend/autoload.php';

spl_autoload_register(function($class) {
	$class = str_replace('\\', '/', $class);
	if(file_exists(DIR_BASE . "../test/$class.php")) {
		include DIR_BASE . "../test/$class.php";
	}
});