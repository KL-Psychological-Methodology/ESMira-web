<?php
require_once '../backend/autoload.php';

use backend\Base;
use backend\Output;

if(!Base::is_init())
	Output::error('ESMira is not ready!');

$post_input = file_get_contents('php://input');

if(strlen($post_input) == 0)
	Output::error('Unexpected data');

if(Base::report($post_input))
	Output::successObj();
else
	Output::error('Internal server error');