<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
require_once 'php/configs.php';
require_once 'php/files.php';
require_once 'php/global_json.php';
require_once 'php/string_fu.php';

$rest_json = file_get_contents('php://input');
if(!($json = json_decode($rest_json)))
	error('Error with data');

if(!isset($json->userId) || !isset($json->studyId) || !isset($json->content) || !isset($json->serverVersion))
	return error('Unexpected data');

if($json->serverVersion < ACCEPTED_SERVER_VERSION)
	error('This app is outdated. Aborting');

$user = $json->userId;
$study_id = $json->studyId;
$content = $json->content;

if(study_is_locked($study_id))
	error("This study is locked");

if(strlen($content) < 2)
	error("Message is too short");
else if(!check_input($user))
	error('User is faulty');

$msg = [
	'from' => $user,
	'content' => $content,
	'sent' => get_milliseconds(),
	'unread' => true
];

$file = get_file_message_unread($study_id, $user);


if(file_exists($file)) {
	$handle = fopen($file, 'r+');
	flock($handle, LOCK_EX);
	if(!$handle)
		error('Something went wrong');
	$messages = unserialize(fread($handle, filesize($file)));
	array_push($messages, $msg);
}
else {
	//TODO: if there are no unread messages and several users write at exactly the same time, they will overwrite each other!
	$handle = fopen($file, 'w');
	flock($handle, LOCK_EX);
	$messages = [$msg];
}

if(fseek($handle, 0) == -1 || !ftruncate($handle, 0)) {
	flock($handle, LOCK_UN);
	fclose($handle);
	error('Internal Server error: Could not save message');
}

if(fwrite($handle, serialize($messages))) {
	fflush($handle);
	flock($handle, LOCK_UN);
	fclose($handle);
	chmod($file, 0666);
	success(json_encode($msg));
}
else {
	flock($handle, LOCK_UN);
	fclose($handle);
	error('Internal Server error: Could not write message');
}

?>