<?php
echo isset($_SERVER['HTACCESS_ENABLED']) ? 1 : '';
require_once '../../backend/autoload.php';

use backend\Output;
use backend\Base;


if(Base::is_init())
	Output::error('Disabled');
else
	Output::success(json_encode([
		'htaccess' => $_SERVER['HTACCESS_ENABLED'],
		'mod_rewrite' => false
	]));