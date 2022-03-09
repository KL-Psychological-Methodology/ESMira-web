<?php

namespace backend\admin\features\messagePermission;

use backend\admin\HasMessagePermission;
use backend\Base;
use backend\Files;
use backend\Output;

class ListMessages extends HasMessagePermission {
	
	function exec() {
		$user = $_GET['user'];
		if(!Base::check_input($user))
			Output::error('Username is faulty');
		
		if(!strlen($user)) {
			$changeMessages = [
				'archive' => [],
				'pending' => [],
				'unread' => []
			];
		}
		else {
			$file_archive = Files::get_file_message_archive($this->study_id, $user);
			$file_pending = Files::get_file_message_pending($this->study_id, $user);
			$file_unread = Files::get_file_message_unread($this->study_id, $user);
			
			$changeMessages = [
				'archive' => file_exists($file_archive) ? unserialize(file_get_contents($file_archive)) : [],
				'pending' => file_exists($file_pending) ? unserialize(file_get_contents($file_pending)) : [],
				'unread' => file_exists($file_unread) ? unserialize(file_get_contents($file_unread)) : []
			];
		}
		
		Output::successObj($changeMessages);
	}
}