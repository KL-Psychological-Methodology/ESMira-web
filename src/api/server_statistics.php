<?php
require_once '../backend/autoload.php';

use backend\Files;
use backend\Base;
use backend\Output;

if(file_exists(Files::get_file_serverStatistics()))
	Output::success(file_get_contents(Files::get_file_serverStatistics()));
else
	Output::success(json_encode(Base::get_fresh_serverStatistics()));