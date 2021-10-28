<?php
header('Content-Type: application/json;charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');

require_once '../config/autoload.php';

use phpClasses\Files;
use phpClasses\Base;
use phpClasses\Output;

if(file_exists(Files::FILE_SERVER_STATISTICS))
	Output::success(file_get_contents(Files::FILE_SERVER_STATISTICS));
else
	Output::success(json_encode(Base::get_fresh_serverStatistics()));