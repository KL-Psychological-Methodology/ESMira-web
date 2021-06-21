<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
require_once 'php/configs.php';
require_once 'php/files.php';
require_once 'php/global_json.php';

$post_input = file_get_contents('php://input');

if(strlen($post_input) == 0)
	error('Unexpected data');

if(report($post_input))
	success(1);
else
	error('Internal server error');

//$filename = time();
//$location = FOLDER_ERRORS.$filename.ERROR_FILE_EXTENSION;
//
//$num = 1;
//while(file_exists($location)) {
//	$location = FOLDER_ERRORS.(++$filename).ERROR_FILE_EXTENSION;
//	if(++$num > 100)
//		error('Too many error-reports on the server');
//}
//
//if(file_put_contents($location, $post_input) && chmod($location, 0666))
//	success(1);
//else
//	error('Internal server error');

?>