<?php
namespace backend\admin;

use backend\Configs;
use backend\Files;
use backend\Output;
use backend\Permission;

abstract class NoPermission {
	protected function create_folder($folder) {
		if(file_exists($folder))
			return;
		mkdir($folder, 0775);
		chmod($folder, 0775);
	}
	
	protected function write_file($file, $s) {
		if(!file_put_contents($file, $s, LOCK_EX)) {
			Output::error('Writing the file \'' . $file . '\' failed');
			return false;
		}
		else {
			chmod($file, 0666);
			return true;
		}
		
	}
	
	protected function check_userExists($user) {
		if(!($h = fopen(Files::get_file_logins(), 'r')))
			return false;
		while(!feof($h)) {
			$line = fgets($h);
			$data = explode(':', $line);
			
			if(!$data || empty($data))
				continue;
			
			if($data[0] == $user) {
				fclose($h);
				return true;
			}
		}
		fclose($h);
		return false;
	}
	
	protected function checkLoginPost() {
		if(!isset($_POST['user']) || !isset($_POST['pass']))
			return;
		$user = $_POST['user'];
		$pass = $_POST['pass'];
		
		$blockTime = 0;
		if(!Permission::check_login($user, $pass, $blockTime)) {
			if($blockTime != 0)
				Output::error("Please wait for $blockTime seconds.");
			else
				Output::error('Wrong password');
			return;
		}
		
		Permission::set_loggedIn($user);
	}
	
	protected function write_serverConfigs($newValues) {
		$saveValues = array_merge(Configs::getDefaultAll(), Configs::getAll(), $newValues);
		
		return $this->write_file(Files::FILE_CONFIG, '<?php return ' . var_export($saveValues, true) . ';');
	}
	
	protected function removeAdd_in_loginsFile($user, $newLineCallback = null) {
		$export = '';
		if(!($h = fopen(Files::get_file_logins(), 'r')))
			return false;
		$userExists = false;
		while(!feof($h)) {
			$line = fgets($h);
			$data = explode(':', $line);
			
			if(!$data || empty($data))
				continue;
			
			if($data[0] != $user)
				$export .= $line;
			else {
				$userExists = true;
				if($newLineCallback)
					$export .= $newLineCallback($user, $data[1]);
			}
		}
		fclose($h);
		if($userExists) {
			$this->write_file(Files::get_file_logins(), $export);
			return true;
		}
		return false;
	}
	
	function __construct() {
	}
	
	abstract function exec();
}

?>