<?php
if(!isset($_GET['image'])) {
	header('Content-Type: application/json;charset=UTF-8');
	header('Cache-Control: no-cache, must-revalidate');
	
	require_once 'php/global_json.php';
	require_once 'php/files.php';
	if(file_exists(FILE_SERVER_STATISTICS))
		success(file_get_contents(FILE_SERVER_STATISTICS));
	else
		success(json_encode(get_fresh_serverStatistics()));
}
else {
	require_once 'php/server_statistics_image.php';
}
?>