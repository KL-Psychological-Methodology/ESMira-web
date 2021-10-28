<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once '../config/autoload.php';

use phpClasses\Base;
use phpClasses\Output;

if(!isset($_POST['study_id']) || !isset($_POST['page_name']))
	Output::error('Missing values');
$study_id = (int) $_POST['study_id'];
$page_name = $_POST['page_name'];

Output::success(!!Base::save_webAccess($study_id, $page_name));
