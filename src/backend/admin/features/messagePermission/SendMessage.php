<?php

namespace backend\admin\features\messagePermission;

use backend\admin\HasMessagePermission;
use backend\Base;
use backend\Files;
use backend\Output;
use backend\Permission;

class SendMessage extends HasMessagePermission {
	private function send_message($study_id, $from, $user, $content) {
		if(!strlen($user))
			return false;
		$msg = [
			'from' => $from,
			'content' => $content,
			'sent' => Base::get_milliseconds(),
			'pending' => true,
			'delivered' => 0
		];
		
		$file = Files::get_file_message_pending($study_id, $user);
		
		if(file_exists($file)) {
			$messages = unserialize(file_get_contents($file));
			array_push($messages, $msg);
		}
		else
			$messages = [$msg];
		
		return $this->write_file($file, serialize($messages));
	}
	
	function exec() {
		$json = json_decode(file_get_contents('php://input'));
		
		if(!$json)
			Output::error('Input is faulty');
		
		$from = Permission::get_user();
		$content = $json->content;
		$toAll = $json->toAll;
		
		
		if(strlen($content) < 2)
			Output::error("Message is too short");
		
		if($toAll) {
			$appVersion = $json->appVersion;
			$appType = isset($json->appType) ? $json->appType : false;
			$checkUserdata = $appVersion || $appType;
			
			
			$usernames_folder = Files::get_folder_userData($this->study_id);
			$count = 0;
			if(file_exists($usernames_folder)) {
				$h_folder = opendir($usernames_folder);
				while($file = readdir($h_folder)) {
					if($file[0] != '.') {
						$user = Files::get_urlFriendly($file);
						if($checkUserdata) {
							$userdata = unserialize(file_get_contents($usernames_folder.$file));
							if(($appVersion && $userdata['appVersion'] != $appVersion) || ($appType &&$userdata['appType'] != $appType))
								continue;
						}
						++$count;
						if(!$this->send_message($this->study_id, $from, $user, $content))
							Output::error("Could not save message for $user. $count messages have already been sent. Aborting now...");
					}
				}
				closedir($h_folder);
			}
		}
		else {
			$user = $json->user;
			if(!Base::check_input($user))
				Output::error('Recipient is faulty');
			
			if(!$this->send_message($this->study_id, $from, $user, $content))
				Output::error("Could not save message");
		}
		
		$c = new MessageSetRead();
		$c->exec();
	}
}