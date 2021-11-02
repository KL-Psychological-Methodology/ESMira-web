<?php
require_once '../backend/autoload.php';

use backend\Base;
use backend\Output;

$post_input = file_get_contents('php://input');

if(strlen($post_input) == 0)
	Output::error('Unexpected data');

if(Base::report($post_input))
	Output::success(1);
else
	Output::error('Internal server error');