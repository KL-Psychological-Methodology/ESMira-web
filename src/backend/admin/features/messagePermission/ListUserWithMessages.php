<?php

namespace backend\admin\features\messagePermission;

use backend\admin\HasMessagePermission;
use backend\Files;
use backend\Output;

class ListUserWithMessages extends HasMessagePermission {
	
	private function indexFolder(&$index, &$msgs, $folder, $attr = false) {
		if(file_exists($folder)) {
			$h_folder = opendir($folder);
			while($file = readdir($h_folder)) {
				if($file[0] != '.') {
					$username = Files::get_urlFriendly($file);
					if(!isset($index[$username])) {
						$index[$username] = true;
						$newMsg = [
							'name' => $username,
							'lastMsg' => filemtime($folder .$file) * 1000
						];
						if($attr) {
							$newMsg[$attr] = true;
						}
						$msgs[] = $newMsg;
					}
				}
			}
			closedir($h_folder);
		}
	}
	
	function exec() {
		$msgs_archive_folder = Files::get_folder_messages_archive($this->study_id);
		$msgs_pending_folder = Files::get_folder_messages_pending($this->study_id);
		$msgs_unread_folder = Files::get_folder_messages_unread($this->study_id);
		
		$changeMessages = [];
		$index = [];
		if(file_exists($msgs_unread_folder))
			$this->indexFolder($index, $changeMessages, $msgs_unread_folder, 'unread');
		if(file_exists($msgs_pending_folder))
			$this->indexFolder($index, $changeMessages, $msgs_pending_folder, 'pending');
		if(file_exists($msgs_archive_folder))
			$this->indexFolder($index, $changeMessages, $msgs_archive_folder);
		
		Output::successObj($changeMessages);
	}
}