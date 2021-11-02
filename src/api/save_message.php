<?php
require_once '../backend/autoload.php';

use backend\Base;
use backend\Files;
use backend\Output;

$rest_json = file_get_contents('php://input');
if(!($json = json_decode($rest_json)))
	Output::error('Error with data');

if(!isset($json->userId) || !isset($json->studyId) || !isset($json->content) || !isset($json->serverVersion))
	Output::error('Unexpected data');

if($json->serverVersion < Base::ACCEPTED_SERVER_VERSION)
	Output::error('This app is outdated. Aborting');

$user = $json->userId;
$study_id = $json->studyId;
$content = $json->content;

if(Base::study_is_locked($study_id))
	Output::error("This study is locked");

if(strlen($content) < 2)
	Output::error("Message is too short");
else if(!Base::check_input($user))
	Output::error('User is faulty');

$msg = [
	'from' => $user,
	'content' => $content,
	'sent' => Base::get_milliseconds(),
	'unread' => true
];

$file = Files::get_file_message_unread($study_id, $user);


if(file_exists($file)) {
	$handle = fopen($file, 'r+');
	flock($handle, LOCK_EX);
	if(!$handle)
		Output::error('Something went wrong');
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
	Output::error('Internal Server error: Could not save message');
}

if(fwrite($handle, serialize($messages))) {
	fflush($handle);
	flock($handle, LOCK_UN);
	fclose($handle);
	chmod($file, 0666);
	Output::success(json_encode($msg));
}
else {
	flock($handle, LOCK_UN);
	fclose($handle);
	Output::error('Internal Server error: Could not write message');
}