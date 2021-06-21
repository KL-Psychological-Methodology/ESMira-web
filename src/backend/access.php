<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once 'php/configs.php';
require_once 'php/files.php';
require_once 'php/global_json.php';

if(!isset($_POST['study_id']) || !isset($_POST['page_name']))
	error('Missing values');
$study_id = (int) $_POST['study_id'];
$page_name = $_POST['page_name'];

success(!!save_webAccess($study_id, $page_name));
?>