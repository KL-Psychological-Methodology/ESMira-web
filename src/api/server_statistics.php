<?php
require_once '../backend/config/autoload.php';

use backend\Files;
use backend\Base;
use backend\Output;

if(file_exists(Files::FILE_SERVER_STATISTICS))
	Output::success(file_get_contents(Files::FILE_SERVER_STATISTICS));
else
	Output::success(json_encode(Base::get_fresh_serverStatistics()));