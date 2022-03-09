<?php

namespace backend\admin\features\noPermission;

use backend\Base;
use backend\Configs;
use backend\Files;
use backend\Output;
use backend\Permission;

class InitESMira extends InitESMiraPrep {
	
	function exec() {
		if(Base::is_init())
			Output::error('Disabled');
		
		$user = $_POST['new_user'];
		$pass = $_POST['pass'];
		$reuseFolder = isset($_POST['reuseFolder']) && $_POST['reuseFolder'];
		
		$dataFolder_path = $this->assemble_data_folderPath($_POST['data_location']);
		
		//
		//create configs file
		//
		$this->write_serverConfigs(['dataFolder_path' => $dataFolder_path]);
		Configs::reload();
		
		//
		// check if data folder already exists
		//
		if(file_exists($dataFolder_path)) {
			if($reuseFolder) { //needs to happen after configs file has been written
				if($this->check_userExists($user))
					$this->removeAdd_in_loginsFile($user); //below, we add the user with the correct password again
			}
			else {
				$count = 2;
				
				do {
					$newPath = substr($dataFolder_path, 0, -1) .$count;
					
					if(++$count > 100)
						Output::error('Too many copies of ' .Files::FILE_CONFIG .' exist');
				} while(file_exists($newPath));
				
				rename($dataFolder_path, $newPath);
				
				$this->create_folder($dataFolder_path);
			}
		}
		else
			$this->create_folder($dataFolder_path);
		
		//
		//prepare data folder:
		//
		$this->write_file($dataFolder_path .'.htaccess', 'Deny from all');
		
		$this->create_folder(Files::get_folder_errorReports());
		$this->create_folder(Files::get_folder_legal());
		$this->create_folder(Files::get_folder_tokenRoot());
		$this->create_folder(Files::get_folder_studies());
		
		if(!file_exists(Files::get_file_studyIndex()))
			$this->write_file(Files::get_file_studyIndex(), serialize([]));
		
		
		//
		//create login:
		//
		if(!file_put_contents(Files::get_file_logins(), $user .':' .Permission::get_hashed_pass($pass) ."\n", FILE_APPEND))
			Output::error('Login data could not be saved');
		
		//
		//create permissions file:
		//
		if(file_exists(Files::get_file_permissions())) {
			$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
			if(!$permissions)
				$permissions = [];
			
			if(!isset($permissions[$user]))
				$permissions[$user] = ['admin' => true];
			else
				$permissions[$user]['admin'] = true;
		}
		else
			$permissions = [$user => ['admin' => true]];
		
		$this->write_file(Files::get_file_permissions(), serialize($permissions));
		
		
		//
		//login:
		//
		Permission::set_loggedIn($user);
		$c = new GetPermissions();
		$c->exec();
	}
}