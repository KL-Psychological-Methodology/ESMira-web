<?php

namespace backend\admin\features\messagePermission;

use backend\admin\HasMessagePermission;
use backend\Files;
use backend\Output;

class MessageSetRead extends HasMessagePermission {
	
	function exec() {
		if(!isset($json))
			$json = json_decode(file_get_contents('php://input'));
		
		$changeMessages = $json->timestamps;
		$user = $json->user;
		
		$file_unread = Files::get_file_message_unread($this->study_id, $user);
		if(!file_exists($file_unread))
			Output::successObj();
		
		$handle_unread = fopen($file_unread, 'r+');
		if(!$handle_unread)
			Output::error("Could not open $file_unread");
		flock($handle_unread, LOCK_EX);
		$messages_unread = unserialize(fread($handle_unread, filesize($file_unread)));
		
		
		
		$file_archive = Files::get_file_message_archive($this->study_id, $user);
		if(file_exists($file_archive)) {
			$handle_archive = fopen($file_archive, 'r+');
			if(!$handle_archive) {
				flock($handle_unread, LOCK_UN);
				fclose($handle_unread);
				Output::error("Could not open $file_archive");
			}
			$messages_archive = unserialize(fread($handle_archive, filesize($file_archive)));
			
			fseek($handle_archive, 0);
			if(!ftruncate($handle_archive, 0)) {
				flock($handle_unread, LOCK_UN);
				fclose($handle_unread);
				fclose($handle_archive);
				Output::error("Could not empty $file_archive");
			}
		}
		else {
			if(!($handle_archive = fopen($file_archive, 'w'))) {
				flock($handle_unread, LOCK_UN);
				fclose($handle_unread);
				Output::error("Could not open $file_archive");
			}
			$messages_archive = [];
		}
		flock($handle_archive, LOCK_EX);
		
		
		foreach($changeMessages as $timestamp) {
			foreach($messages_unread as $index => $msg) {
				if($msg['sent'] == $timestamp) {
					unset($msg['unread']);
					$messages_archive[] = $msg;
					unset($messages_unread[$index]);
					break;
				}
			}
		}
		
		
		$error = false;
		if(count($messages_unread)) {
			fseek($handle_unread, 0);
			if(!ftruncate($handle_unread, 0))
				$error = "Could not empty $file_unread";
			else if(!fwrite($handle_unread, serialize($messages_unread)))
				$error = "Could not write to $file_unread";
		}
		else if(!unlink($file_unread))
			$error = "Could not delete $file_unread";
		
		
		if(!$error && !fwrite($handle_archive, serialize($messages_archive)))
			$error = "Could not write to $file_archive";
		
		
		fflush($handle_unread);
		fflush($handle_archive);
		flock($handle_unread, LOCK_UN);
		flock($handle_archive, LOCK_UN);
		fclose($handle_unread);
		fclose($handle_archive);
		
		if($error)
			Output::error($error);
		else
			Output::successObj();
	}
}