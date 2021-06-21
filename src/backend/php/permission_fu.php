<?php
require_once 'configs.php';
require_once 'files.php';


//Thanks to:
//https://github.com/whitehat101/apr1-md5

//The MIT License (MIT)

//Copyright (c) 2015 Jeremy

//Permission is hereby granted, free of charge, to any person obtaining a copy
//of this software and associated documentation files (the "Software"), to deal
//in the Software without restriction, including without limitation the rights
//to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
//copies of the Software, and to permit persons to whom the Software is
//furnished to do so, subject to the following conditions:

//The above copyright notice and this permission notice shall be included in all
//copies or substantial portions of the Software.

//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
//IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
//FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
//AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
//LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
//OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
//SOFTWARE.
class Hasher {
    const BASE64_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    const APRMD5_ALPHABET = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    // Source/References for core algorithm:
    // http://www.cryptologie.net/article/126/bruteforce-apr1-hashes/
    // http://svn.apache.org/viewvc/apr/apr-util/branches/1.3.x/crypto/apr_md5.c?view=co
    // http://www.php.net/manual/en/function.crypt.php#73619
    // http://httpd.apache.org/docs/2.2/misc/password_encryptions.html
    // Wikipedia
    public static function hash_complete($hash, $salt) {
        return '$apr1$'.$salt.'$'.$hash;
    }
    
    public static function hash($mdp, &$salt) {
		if (is_null($salt))
			$salt = self::salt();
		$salt = substr($salt, 0, 8);
		$max = strlen($mdp);
		$context = $mdp.'$apr1$'.$salt;
		$binary = pack('H32', md5($mdp.$salt.$mdp));
		for($i=$max; $i>0; $i-=16)
			$context .= substr($binary, 0, min(16, $i));
		for($i=$max; $i>0; $i>>=1)
			$context .= ($i & 1) ? chr(0) : $mdp[0];
		$binary = pack('H32', md5($context));
		for($i=0; $i<1000; $i++) {
			$new = ($i & 1) ? $mdp : $binary;
			if($i % 3) $new .= $salt;
			if($i % 7) $new .= $mdp;
			$new .= ($i & 1) ? $binary : $mdp;
			$binary = pack('H32', md5($new));
		}
		$hash = '';
		for ($i = 0; $i < 5; $i++) {
			$k = $i+6;
			$j = $i+12;
			if($j == 16) $j = 5;
			$hash = $binary[$i].$binary[$k].$binary[$j].$hash;
		}
		$hash = chr(0).chr(0).$binary[11].$hash;
		$hash = strtr(
			strrev(substr(base64_encode($hash), 2)),
			self::BASE64_ALPHABET,
			self::APRMD5_ALPHABET
		);
		return $hash;
	}
    
    // 8 character salts are the best. Don't encourage anything but the best.
    public static function salt() {
        $alphabet = self::APRMD5_ALPHABET;
        $salt = '';
        for($i=0; $i<8; $i++) {
            $offset = hexdec(bin2hex(openssl_random_pseudo_bytes(1))) % 64;
            $salt .= $alphabet[$offset];
        }
        return $salt;
    }
    public static function check_hash($hash_lite, $check_hash) {
        $parts = explode('$', $check_hash);
        $salt = $parts[2];
//        echo '"' .trim(self::hash_complete($hash_lite, $salt)).'" == "'.trim($check_hash) .'"';
        return (trim(self::hash_complete($hash_lite, $salt)) == trim($check_hash));
    }
    
    public static function plain_to_hashLite($plain, $check_hash) {
		$parts = explode('$', $check_hash);
		$salt = $parts[2];
		return self::hash($plain, $salt);
	}
}

function get_hashed_pass($pass) {
	$salt = null;
	$hash_lite = Hasher::hash($pass, $salt);
	return Hasher::hash_complete($hash_lite, $salt);
}


function check_local() {
	return $_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1';
}


function load_loginFile() {
	if(!file_exists(FILE_LOGINS))
		return false;
	return fopen(FILE_LOGINS, 'r');
}
function interpret_userLine($h) {
	$line = substr(fgets($h), 0, -1);
	if($line == '')
		return false;
	return explode(':', $line);
}

function load_hashLite_from_login($user, $plain) {
	$h = load_loginFile();
	if($h) {
		while(!feof($h)) {
			$data = interpret_userLine($h);
			
			if($data && $data[0] == $user) {
				$hash_lite = Hasher::plain_to_hashLite($plain, $data[1]);
				
//				echo Hasher::check_hash($hash_lite, $data[1]) ? 111 : 22;
				if(Hasher::check_hash($hash_lite, $data[1]))
					return $hash_lite;
			}
		}
		fclose($h);
	}
	return false;
}

function is_loggedIn() {
	if(!isset($_COOKIE['user']) || !isset($_COOKIE['pass']))
		return false;
	$user = $_COOKIE['user'];
	$hash_lite = $_COOKIE['pass'];
	
	$h = load_loginFile();
	if($h) {
		while(!feof($h)) {
			$data = interpret_userLine($h);
			
			if($data && $data[0] == $user && Hasher::check_hash($hash_lite, $data[1]))
				return true;
		}
		fclose($h);
	}
	return false;
}

function create_readPermission_htaccessFile($study_id, $permissions=null) {
	if($permissions == null)
		$permissions = unserialize(file_get_contents(FILE_PERMISSIONS));
	
	$s = '';
	if($permissions) {
		foreach($permissions as $user => $perm) {
			if(isset($perm['admin']) && $perm['admin']) {
				$s .= sprintf(HTACCESS_REQUIRE_LINE, $user);
			}
			if(isset($perm['read'])) {
				foreach($perm['read'] as $id) {
					if($id == $study_id) {
						$s .= sprintf(HTACCESS_REQUIRE_LINE, $user);
						break;
					}
				}
			}
		}
	}
	
	$file = get_folder_responses($study_id).FILENAME_HTACCESS;
	if(!file_put_contents($file, sprintf(HTACCESS_RESPONSES_TEMPLATE, realpath(FILE_LOGINS), $s), LOCK_EX)) {
		error('Could not secure ' .get_folder_responses($study_id) .'! All study data may be reachable without password!');
		return false;
	}
	else {
		chmod($file, 0666);
		return true;
	}
}

function get_user() {
	return  $_COOKIE['user'];
}

function is_admin() {
	if(!isset($_COOKIE['user']) || !file_exists(FILE_PERMISSIONS))
		return false;

	$user = get_user();
	$permissions = unserialize(file_get_contents(FILE_PERMISSIONS));
	return $permissions && isset($permissions[$user]) && isset($permissions[$user]['admin']) && $permissions[$user]['admin'];
//	return $permissions && isset($permissions['admins']) && in_array($user, $permissions['admins']);
}
function has_permission($study_id, $permCode) {
	if(!isset($_COOKIE['user']) || !file_exists(FILE_PERMISSIONS))
		return false;
	
	$user = get_user();
	$permissions = unserialize(file_get_contents(FILE_PERMISSIONS));
	return $permissions && isset($permissions[$user]) && isset($permissions[$user][$permCode]) && in_array($study_id, $permissions[$user][$permCode]);
//	return $permissions && isset($permissions[$permCode]) && isset($permissions[$permCode][$user]) && in_array($study_id, $permissions[$permCode][$user]);
}
function get_permissions() {
	$user = get_user();
	$permissions = unserialize(file_get_contents(FILE_PERMISSIONS));
	if(!$permissions || !isset($permissions[$user]))
		return [];
	else
		return $permissions[$user];
}



//function is_loggedIn($user = null, $pass = null) {
//	if(is_null($user)){
//		if(!isset($_COOKIE['user']) || !isset($_COOKIE['pass']))
//			return false;
//		$user = $_COOKIE['user'];
//		$pass = $_COOKIE['pass'];
//
//		$fu = 'Hasher::check_hash';
//	}
//	else
//		$fu = 'Hasher::check_pass';
//
//	if(!file_exists(FILE_LOGINS))
//		return false;
//	$h = fopen(FILE_LOGINS, 'r');
//	while(!feof($h)) {
//		$line = substr(fgets($h), 0, -1);
//		if($line == '')
//			continue;
//		$data = explode(':', $line);
//
//		if($data[0] == $user && $r = $fu($pass, $data[1])) {
//			return $r;
//			break;
//		}
//	}
//	fclose($h);
//	return false;
//}


?>