<?php
require_once '../backend/autoload.php';

use backend\Configs;
use backend\Files;
use backend\Output;
use backend\Base;

if(!Base::is_init())
	Output::error('ESMira is not ready!');

function moveTo_archive($study_id, $user_id, $to_archive) {
	if(!count($to_archive))
		return;
	$file_archive = Files::get_file_message_archive($study_id, $user_id);
	
	
	if(file_exists($file_archive)) {
		$handle_archive = fopen($file_archive, 'r+');
		$msgs_archive = unserialize(fread($handle_archive, filesize($file_archive)));
	}
	else {
		$handle_archive = fopen($file_archive, 'w');
		$msgs_archive = [];
	}
	if(!$handle_archive)
		return;
	flock($handle_archive, LOCK_EX);
	
	foreach($to_archive as $msg) {
		unset($msg['delivered']);
		unset($msg['pending']);
		
		$msgs_archive[] = $msg;
	}
	
	$max_msgs_per_user = Configs::get('max_msgs_per_user');
	if(count($msgs_archive) > $max_msgs_per_user)
		$msgs_archive = array_slice($msgs_archive, count($msgs_archive) - $max_msgs_per_user, $max_msgs_per_user);
	
	fseek($handle_archive, 0);
	if(!ftruncate($handle_archive, 0))
		Base::report("Internal server error");
	
	if(!fwrite($handle_archive, serialize($msgs_archive)))
		Output::error("Internal server error");
	chmod($file_archive, 0666);
	
	fflush($handle_archive);
	flock($handle_archive, LOCK_UN);
	fclose($handle_archive);
}


$rest_json = file_get_contents('php://input');
if(!($json = json_decode($rest_json)) || !isset($json->dataset) || !isset($json->userId) || !isset($json->serverVersion))
	Output::error('Error with data');

if($json->serverVersion < Base::ACCEPTED_SERVER_VERSION)
	Output::error('This app is outdated. Aborting');

$data = $json->dataset;
$user_id = $json->userId;

//$output = [];
$output = new stdClass();

foreach($data as $study_id => $line) {
	if($study_id != (int)$study_id || !isset($line->version) || !isset($line->msgTimestamp))
		Output::error('Unexpected input');
	
	$metadata_path = Files::get_file_studyMetadata($study_id);
	if(!file_exists($metadata_path) || Base::study_is_locked($study_id))
		continue;
	
	$metadata = unserialize(file_get_contents($metadata_path));
	
	
	$version = $line->version;
	$forceStudyUpdate = isset($line->forceStudyUpdate) && $line->forceStudyUpdate;
	$last_message = $line->msgTimestamp;
	
	
	if(isset($metadata['accessKeys']) && sizeof($metadata['accessKeys']) && (!isset($line->accessKey) || !in_array(strtolower($line->accessKey), $metadata['accessKeys'])))
		Output::error("Wrong accessKey: $line->accessKey");
	
	
	$line = [];
	
	//messages:
	$file_pending = Files::get_file_message_pending($study_id, $user_id);
	
	if(file_exists($file_pending)) {
		$msgs = unserialize(file_get_contents($file_pending));
		
		if($last_message < filemtime($file_pending) * 1000) {
			$to_archive = [];
			$new_msgs = [];
			$output_msgs = [];
			foreach($msgs as $index => &$cMsg) {
				if($last_message < $cMsg['sent']) {
					$output_msgs[] = [
						'content' => $cMsg['content'],
						'sent' => $cMsg['sent']
					];
					
					$cMsg['read'] = time();
					$cMsg['delivered'] += 1;
					$new_msgs[] = $cMsg;
				}
				else
					$to_archive[] = $cMsg;
			}
			if(count($new_msgs)) {
				if(!file_put_contents($file_pending, serialize($new_msgs), LOCK_EX))
					Output::error("Internal server error");
				chmod($file_pending, 0666);
			}
			else {
				if(!unlink($file_pending))
					Output::error("Internal server error");
			}
			
			if(count($msgs))
				$line['msgs'] = $new_msgs;
			
			moveTo_archive($study_id, $user_id, $to_archive);
		}
		else {
			moveTo_archive($study_id, $user_id, $msgs);
			if(!unlink($file_pending))
				Output::error("Internal server error");
		}
	}
	
	
	//studies
	if($forceStudyUpdate || $metadata['version'] > $version) {
		$filename_lang = Files::get_file_langConfig($study_id, Base::get_lang(false));
		$filename = Files::get_file_studyConfig($study_id);
		if($filename_lang && file_exists($filename_lang))
            $study_json = file_get_contents($filename_lang);
		else if(file_exists($filename))
            $study_json = file_get_contents($filename);
		else
			Output::error('Internal server error');
		
		//TODO: $study_json is a String, so we need to turn it into an object first or JSON will format it as a string.
		// This is a waste of performance. So a better solution would be to just concat the JSON string manually which is ugly
		$line['study'] = json_decode($study_json);
	}
	
	
	if(!empty($line))
		$output->{$study_id} = $line;
}
Output::successObj($output);