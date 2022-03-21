<?php

namespace backend;

use stdClass;

class UserTokens {
	private $new_studyTokens = [];
	private $current_studyTokens = [];
	private $file_handles = [];
	private $dataSetCount = [];
	private $userIds = [];
	
	private $user_id;
	private $app_version;
	private $app_type;
	
	private $is_newUser = false;
	
	function __construct($user_id, $app_version, $app_type) {
		$this->user_id = $user_id;
		$this->app_version = $app_version;
		$this->app_type = $app_type;
	}
	
	private function get_newUserData($study_id) {
		return [
			'userId' => $this->userIds[$study_id],
			'token' => $this->get_newToken($study_id),
			'dataSetCount' => $this->dataSetCount[$study_id],
			'appVersion' => $this->app_version,
			'appType' => $this->app_type
		];
	}
	
	private function load_userData($study_id) {
		$file_token = Files::get_file_userData($study_id, $this->user_id);
		
		if(file_exists($file_token)) {
			$handle = fopen($file_token, 'r+');
			
			if($handle) {
				$this->file_handles[$study_id] = $handle;
				$userdata = unserialize(fread($handle, filesize($file_token)));
				if(isset($userdata['dataSetCount']))
					$this->addTo_dataSetCount($study_id, $userdata['dataSetCount']);
				
				$this->userIds[$study_id] = isset($userdata['userId']) ? $userdata['userId'] : $this->create_newUserId($study_id);
				$this->current_studyTokens[$study_id] = $userdata['token'];
			}
			else {
				Base::report("Could not open token for user \"$this->user_id\" in study $study_id");
				$this->userIds[$study_id] = $this->create_newUserId($study_id);
				$this->current_studyTokens[$study_id] = -1;
			}
		}
		else {
			$handle = fopen($file_token, 'w');
			
			if($handle)
				$this->file_handles[$study_id] = $handle;
			else
				Base::report("Could not create token for user \"$this->user_id\" in study $study_id");
			
			$this->userIds[$study_id] = $this->create_newUserId($study_id);
			$this->is_newUser = true;
			$this->current_studyTokens[$study_id] = -1;
		}
		
		flock($handle, LOCK_EX);
	}
	
	private function get_newToken($study_id) {
		if(isset($this->new_studyTokens[$study_id]))
			return $this->new_studyTokens[$study_id];
		else
			return $this->new_studyTokens[$study_id] = Base::get_milliseconds();
	}
	
	private function create_newUserId($study_id) {
		$count = 0;
		$h_folder = opendir(Files::get_folder_userData($study_id));
		while($folder = readdir($h_folder)) {
			if($folder[0] != '.') {
				++$count;
			}
		}
		closedir($h_folder);
		return $count;
	}
	
	private function addTo_dataSetCount($study_id, $count) {
		if(isset($this->dataSetCount[$study_id]))
			$this->dataSetCount[$study_id] += $count;
		else
			$this->dataSetCount[$study_id] = $count;
	}
	
	
	public function nextDataSet($study_id) {
		$this->load_userData($study_id);
		$this->addTo_dataSetCount($study_id, 1);
		
		$currentToken = $this->current_studyTokens[$study_id];
		$newToken = $this->get_newToken($study_id);
		
		return $this->is_newUser || $newToken - $currentToken >= Configs::get('dataset_server_timeout');
	}
	
	public function is_outdated($study_id, $sentToken, $is_reupload) {
		if(!$is_reupload || $sentToken == 0)
			return false;
		return $sentToken != $this->current_studyTokens[$study_id];
	}
	public function is_newUser() {
		return $this->is_newUser;
	}
	
	public function get_newStudyTokens() {
		if(empty($this->new_studyTokens))
			return new stdClass(); //will be serialized into json - stdClass makes sure it stays an object even when its empty
		else
			return $this->new_studyTokens;
	}
	public function get_dataSetId($study_id) {
		return $this->userIds[$study_id] * 1000000 + $this->dataSetCount[$study_id];
	}
	
	public function writeAndClose() {
		foreach($this->file_handles as $study_id => $handle) {
			$userdata = $this->get_newUserData($study_id);
			
			fseek($handle, 0);
			ftruncate($handle, 0);
			
			if(!fwrite($handle, serialize($userdata))) {
				Base::report("Could not save token for user \"$this->user_id\" in study $study_id");
				$this->new_studyTokens[$study_id] = -1;
			}
			
			fflush($handle);
			flock($handle, LOCK_UN);
			fclose($handle);
		}
		$this->file_handles = [];
	}
}