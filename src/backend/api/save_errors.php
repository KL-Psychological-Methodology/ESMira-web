<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once '../config/autoload.php';

use phpClasses\Base;
use phpClasses\Output;

$post_input = file_get_contents('php://input');

if(strlen($post_input) == 0)
	Output::error('Unexpected data');

if(Base::report($post_input))
	Output::success(1);
else
	Output::error('Internal server error');