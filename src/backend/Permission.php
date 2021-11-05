<?php

namespace backend;

use Exception;
use backend\Files;
use backend\Base;

class Permission {
	static function create_folder($folder) {
		mkdir($folder, 0775);
		chmod($folder, 0775);
	}
	static function check_pass($plain, $hashed) {
		return password_verify($plain, $hashed);
	}
	
	static function get_hashed_pass($pass) {
		return password_hash($pass,  PASSWORD_DEFAULT);
	}
	
	static function load_loginFile() {
		if(!file_exists(Files::get_file_logins()))
			return false;
		return fopen(Files::get_file_logins(), 'r');
	}
	static function interpret_userLine($h) {
		$line = substr(fgets($h), 0, -1);
		if($line == '')
			return false;
		return explode(':', $line);
	}
	
	static function check_login($user, $plain, &$blockTIme) {
		//check if login is blocked:
		$file_blocking = Files::get_file_blockLogin($user);
		$has_blockingFile = file_exists($file_blocking);
		if($has_blockingFile) {
			$diff = (filemtime($file_blocking) + (int)file_get_contents($file_blocking)) - time();
			if($diff > 0) {
				$blockTIme = $diff;
				return false;
			}
		}
		
		//search for user:password:
		$userExists = false;
		$h = self::load_loginFile();
		if($h) {
			while(!feof($h)) {
				$data = self::interpret_userLine($h);
				
				if($data && $data[0] == $user) {
					$userExists = true;
					if(self::check_pass($plain, $data[1])) {
						if($has_blockingFile)
							unlink($file_blocking);
						self::save_loginHistory($user, 'Login form');
						return true;
					}
				}
			}
			fclose($h);
		}
		
		//block next login for a while because of failed attempt:
		usleep(rand(0, 1000000)); //to prevent Timing Leaks
		
		if($userExists) { //TODO: the timeout only happens when a user exists. This can be used to find out usernames. Whats the best solution for that?
			$folder_token = Files::get_folder_token($user);
			
			if(!file_exists($folder_token))
				self::create_folder($folder_token);
			if(!file_exists($file_blocking))
				file_put_contents($file_blocking, 1);
			else {
				$num = (int)file_get_contents($file_blocking);
				file_put_contents($file_blocking, min($num * 2, 1800));
			}
			self::save_loginHistory($user, 'Failed login attempt');
		}
		return false;
	}
	
	static function set_loggedIn($user) {
		if(session_status() !== PHP_SESSION_ACTIVE)
			session_start();
		$_SESSION['user'] = $user;
		$_SESSION['is_loggedIn'] = true;
	}
	static function set_loggedOut() {
		if(session_status() !== PHP_SESSION_ACTIVE)
			session_start();
		
		$_SESSION['is_loggedIn'] = false;
		$_SESSION['user'] = null;
		
		if(isset($_COOKIE['tokenId']) && isset($_COOKIE['user'])) {
			$file_token = Files::get_file_token($_COOKIE['user'], $_COOKIE['tokenId']);
			if(file_exists($file_token))
				unlink($file_token);
		}
		
		Base::delete_cookie('user');
		Base::delete_cookie('tokenId');
		Base::delete_cookie('tokenHash');
	}
	
	static function is_loggedIn() {
		switch(session_status()) {
			case PHP_SESSION_DISABLED:
				throw new Exception('This server does not support sessions!');
			case PHP_SESSION_NONE:
				session_start();
				if(isset($_SESSION['is_loggedIn']) && $_SESSION['is_loggedIn'])
					return true;
				
				if(!isset($_COOKIE['user']) || !isset($_COOKIE['tokenId']) || !isset($_COOKIE['tokenHash']))
					return false;
				
				usleep(rand(0, 1000000)); //to prevent Timing Leaks
				
				$user = $_COOKIE['user'];
				$token_id = $_COOKIE['tokenId'];
				$token_hash = $_COOKIE['tokenHash'];
				
				$file_token = Files::get_file_token($user, $token_id);
				
				if(!file_exists($file_token)) { //can happen when a login was removed; also the tokenId cookie is not critical
					self::set_loggedOut();
					return false;
				}
				else if(self::hash_token($token_hash) !== file_get_contents($file_token)) { //Something fishy is going on
					$ip = $_SERVER['REMOTE_ADDR'];
					$userAgent = $_SERVER['HTTP_USER_AGENT'];
					Base::report("Somebody tried to log in with a broken token. All token for that user have been deleted for security reasons.\nUser: $user,\nIp: $ip,\ntokenId: $token_id,\ntokenHash: $token_hash,\nUserAgent: $userAgent");
					
					$folder_token = Files::get_folder_token($user);
					$h_folder = opendir($folder_token);
					while($file = readdir($h_folder)) {
						if($file[0] != '.')
							unlink($folder_token.$file);
					}
					closedir($h_folder);
					self::set_loggedOut();
					return false;
				}
				self::save_loginHistory($user, $token_id);
				
				//Note: Should we renew the token every time? I am not sure.
				// - On the one hand it is good to renew the token to make sure every token can only be used (in the long term) on one device.
				//     This makes it easier to spot if a token has been stolen (because the user gets logged out and there will be another active entry in the token list).
				// - On the other hand, if two token login requests are done at the same time (with the same cookies):
				//     RequestA will change the token_hash which will make RequestB fail and trigger a report because its cookie still has the old token_hash.
				//     Though unlikely, it might happen for example when a browser restores multiple tabs from an old session.
				self::create_token($user, $token_id);
				
				self::set_loggedIn($user);
				
				return true;
			case PHP_SESSION_ACTIVE:
				return isset($_SESSION['is_loggedIn']) && $_SESSION['is_loggedIn'];
		}
		
		return false;
	}
	
	static function get_user() {
		return $_SESSION['user'];
	}
	
	static function get_currentToken() {
		return isset($_COOKIE['tokenId']) ? $_COOKIE['tokenId'] : null;
	}
	
	static function is_admin() {
		if(!file_exists(Files::get_file_permissions()))
			return false;
		
		$user = self::get_user();
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		return $permissions && isset($permissions[$user]) && isset($permissions[$user]['admin']) && $permissions[$user]['admin'];
//	return $permissions && isset($permissions['admins']) && in_array($user, $permissions['admins']);
	}
	static function has_permission($study_id, $permCode) {
		if(!file_exists(Files::get_file_permissions()))
			return false;
		
		$user = self::get_user();
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		return $permissions && isset($permissions[$user]) && isset($permissions[$user][$permCode]) && in_array($study_id, $permissions[$user][$permCode]);
//	return $permissions && isset($permissions[$permCode]) && isset($permissions[$permCode][$user]) && in_array($study_id, $permissions[$permCode][$user]);
	}
	static function get_permissions() {
		$user = self::get_user();
		$permissions = unserialize(file_get_contents(Files::get_file_permissions()));
		if(!$permissions || !isset($permissions[$user]))
			return [];
		else
			return $permissions[$user];
	}
	
	static function save_loginHistory($user, $login) {
		$folder_token = Files::get_folder_token($user);
		if(!file_exists($folder_token))
			self::create_folder($folder_token);
		
		$file_tokenHistory1 = Files::get_file_tokenHistory($user, 1);
		$file_tokenHistory2 = Files::get_file_tokenHistory($user, 2);
		
		if(!file_exists($file_tokenHistory1)) { //The very first entry is saved in $file_tokenHistory1
			$file_tokenHistory = $file_tokenHistory1;
			$flag = LOCK_EX;
		}
		else if(!file_exists($file_tokenHistory2)) { //The very second entry is saved in $file_tokenHistory2
			$file_tokenHistory = $file_tokenHistory2;
			$flag = LOCK_EX;
		}
		else { //Both files have been created:
			$now = time();
			$target = 60*60*24*60;
			$diff_1 = $now - filemtime($file_tokenHistory1);
			$diff_2 = $now - filemtime($file_tokenHistory2);
			
			if($diff_1 < $target && $diff_2 < $target) { //as long as no history file gets to old, always add to the most recent one
				$file_tokenHistory = $diff_1 < $diff_2 ? $file_tokenHistory1 : $file_tokenHistory2;
				$flag = FILE_APPEND | LOCK_EX;
			}
			else { //until a history file gets too old. Then overwrite it (which will then become the most recent one)
				$file_tokenHistory = $diff_1 > $diff_2 ? $file_tokenHistory1 : $file_tokenHistory2; // overwrite the oldest one in case both are old
				$flag = LOCK_EX;
			}
		}
		
		$csv_delimiter = Configs::get('csv_delimiter');
		$data = "\n\"".time().'"' .$csv_delimiter .'"'.$login.'"' .$csv_delimiter .'"'.$_SERVER['REMOTE_ADDR'].'"' .$csv_delimiter .'"'. Base::strip_oneLineInput($_SERVER['HTTP_USER_AGENT']) .'"';
		file_put_contents($file_tokenHistory, $data, $flag);
	}
	
	static function randomToken($length) {
		//Thanks to: https://www.php.net/manual/en/function.random-bytes.php#118932
		if (function_exists('random_bytes'))
			return bin2hex(random_bytes($length));
		else if (function_exists('mcrypt_create_iv'))
			return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
		else if (function_exists('openssl_random_pseudo_bytes'))
			return bin2hex(openssl_random_pseudo_bytes($length));
		else
			return false;
	}
	
	static function remove_token($user, $token_id) {
		$file_token = Files::get_file_token($user, $token_id);
		if(file_exists($file_token))
			unlink($file_token);
	}
	static function hash_token($token_hash) {
		//simple hashing is enough. If somebody has access to our data, they dont need the random token anymore
		return hash('sha256', $token_hash);
	}
	
	static function create_token($user, $token_id = null) {
		$folder_token = Files::get_folder_token($user);
		if(!file_exists($folder_token))
			self::create_folder($folder_token);
		
		//create hashes:
		if($token_id === null) {
			do {
				$token_id = self::randomToken(16);
				if(!$token_id)
					return;
			} while (file_exists(Files::get_file_token($user, $token_id)));
		}
		$token_hash = self::randomToken(32);
		if(!$token_hash)
			return;
		
		file_put_contents(Files::get_file_token($user, $token_id), self::hash_token($token_hash), LOCK_EX);
		
		//save cookies:
		$expire = time()+31536000; //60*60*24*365
		Base::create_cookie('user', $_COOKIE['user'] = $user, $expire);
		Base::create_cookie('tokenId', $_COOKIE['tokenId'] = $token_id, $expire);
		Base::create_cookie('tokenHash', $_COOKIE['tokenHash'] = $token_hash, $expire);
	}
}