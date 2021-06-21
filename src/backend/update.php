<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
require_once 'php/configs.php';
require_once 'php/files.php';
require_once 'php/global_json.php';

function moveTo_archive($study_id, $user_id, $to_archive) {
	if(!count($to_archive))
		return;
	$file_archive = get_file_message_archive($study_id, $user_id);
	
//	$handle_archive = fopen($file_archive, 'r+');
//	if(!$handle_archive)
//		return;
//	$msgs_archive = file_exists($file_archive) ? unserialize(fread($handle_archive, filesize($file_archive))) : [];
	
	
	if(file_exists($file_archive)) {
		$handle_archive = fopen($file_archive, 'r+');
		if(!$handle_archive)
			return;
		$msgs_archive = unserialize(fread($handle_archive, filesize($file_archive)));
	}
	else {
		$handle_archive = fopen($file_archive, 'w');
		$msgs_archive = [];
	}
	flock($handle_archive, LOCK_EX);
	
	foreach($to_archive as $msg) {
		unset($msg['delivered']);
		unset($msg['pending']);
		
		$msgs_archive[] = $msg;
	}
	
	if(count($msgs_archive) > MAX_MSGS_PER_USER)
		$msgs_archive = array_slice($msgs_archive, count($msgs_archive) - MAX_MSGS_PER_USER, MAX_MSGS_PER_USER);
	
	fseek($handle_archive, 0);
	if(!ftruncate($handle_archive, 0))
		report("Internal server error");
	
	if(!fwrite($handle_archive, serialize($msgs_archive)))
		error("Internal server error");
	chmod($file_archive, 0666);
	
	fflush($handle_archive);
	flock($handle_archive, LOCK_UN);
	fclose($handle_archive);
}


$rest_json = file_get_contents('php://input');
if(!($json = json_decode($rest_json)) || !isset($json->dataset) || !isset($json->userId) || !isset($json->serverVersion))
	error('Error with data');

if($json->serverVersion < ACCEPTED_SERVER_VERSION)
	error('This app is outdated. Aborting');

$data = $json->dataset;
$user_id = $json->userId;

//$output = [];
$output = new stdClass();

foreach($data as $study_id => $line) {
	if($study_id != (int)$study_id || !isset($line->version) || !isset($line->msgTimestamp))
		error('Unexpected input');
	
	$metadata_path = get_file_studyMetadata($study_id);
	if(!file_exists($metadata_path) || study_is_locked($study_id))
		continue;
	
	$metadata = unserialize(file_get_contents($metadata_path));
	
	
	$version = $line->version;
	$last_message = $line->msgTimestamp;
	
	
	if(isset($metadata['accessKeys']) && sizeof($metadata['accessKeys']) && (!isset($line->accessKey) || !in_array($line->accessKey, $metadata['accessKeys'])))
		error('Wrong accessKey');
	
	
	$line = [];
	
	//messages:
	$file_pending = get_file_message_pending($study_id, $user_id);
	
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
					error("Internal server error");
				chmod($file_pending, 0666);
			}
			else {
				if(!unlink($file_pending))
					error("Internal server error");
			}
			
			if(count($msgs))
				$line['msgs'] = $new_msgs;
			
			moveTo_archive($study_id, $user_id, $to_archive);
		}
		else {
			moveTo_archive($study_id, $user_id, $msgs);
			if(!unlink($file_pending))
				error("Internal server error");
		}
	}
	
	
	//studies
	if($metadata['version'] > $version) {
		$filename = get_file_studyConfig($study_id);
		
		if(file_exists($filename)) {
			$study = file_get_contents($filename);
			//TODO $study is a String, so we need to turn it into an object first.
			// This is a waste of performance. So a better solution would be to just concat the JSON string manually which is ugly
			$line['study'] = json_decode($study);
		}
		else
			error('Internal server error');
	}
	
	
	if(count($line) > 0)
		$output->{$study_id} = $line;
}
success(json_encode($output));
?>