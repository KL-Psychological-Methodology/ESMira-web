<?php
header('Content-Type: application/json;charset=UTF-8');
require_once 'php/global_json.php';
require_once 'php/files.php';
$obj = [];
if(file_exists(FILE_IMPRESSUM))
	$obj['impressum'] = file_get_contents(FILE_IMPRESSUM);

if(file_exists(FILE_PRIVACY_POLICY))
	$obj['privacyPolicy'] = file_get_contents(FILE_PRIVACY_POLICY);

success(json_encode($obj));
?>