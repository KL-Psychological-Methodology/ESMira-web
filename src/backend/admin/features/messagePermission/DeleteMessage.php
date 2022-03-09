<?php

namespace backend\admin\features\messagePermission;

use backend\admin\HasMessagePermission;
use backend\Files;
use backend\Output;

class DeleteMessage extends HasMessagePermission {
	
	function exec() {
		$user = $_POST['user'];
		$sent = $_POST['sent'];
		$msgs_pending_folder = Files::get_folder_messages_pending($this->study_id);
		
		$file_pending = Files::get_file_message_pending($this->study_id, $user);
		if(!file_exists($file_pending))
			Output::error('Message does not exist');
		
		$changeMessages = unserialize(file_get_contents($file_pending));
		
		foreach($changeMessages as $index => $cMsg) {
			if($cMsg['sent'] == $sent) {
				array_splice($changeMessages, $index, 1);
				break;
			}
		}
		
		if(count($changeMessages) === 0) {
			if(unlink($file_pending))
				Output::successObj([]);
			else
				Output::error("Could not delete $file_pending");
		}
		else if($this->write_file($file_pending, serialize($changeMessages)))
			Output::successObj($changeMessages);
		else
			Output::error("Could not save message");
	}
}