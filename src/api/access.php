<?php

require_once '../backend/config/autoload.php';

use backend\Base;
use backend\Output;

if(!isset($_POST['study_id']) || !isset($_POST['page_name']))
	Output::error('Missing values');
$study_id = (int) $_POST['study_id'];
$page_name = $_POST['page_name'];

Output::success(!!Base::save_webAccess($study_id, $page_name));
