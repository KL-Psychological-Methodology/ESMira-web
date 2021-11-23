<?php

require_once '../backend/autoload.php';

use backend\Base;
use backend\Output;

if(!Base::is_init())
	Output::error('ESMira is not ready!');

if(!isset($_POST['study_id']) || !isset($_POST['page_name']))
	Output::error('Missing values');
$study_id = (int) $_POST['study_id'];
$page_name = $_POST['page_name'];

Output::successObj((bool) Base::save_webAccess($study_id, $page_name));
