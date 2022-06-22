<?php

namespace backend;

class Permission {
	static function getHashedPass(string $pass) {
		return password_hash($pass,  PASSWORD_DEFAULT);
	}
	
	/**
	 * @throws PageFlowException
	 * @throws CriticalError
	 */
	static function login(string $user, string $password) {
		$userStore = Configs::getDataStore()->getUserStore();
		$blockTime = $userStore->getUserBlockedTime($user);
		if($blockTime != 0)
			throw new PageFlowException("Please wait for $blockTime seconds.");
		
		if(!$userStore->checkUserLogin($user, $password)) {
			usleep(rand(0, 1000000)); //to prevent Timing Leaks
			
			//block next login for a while because of failed attempt:
			$userStore->createBlocking($user);
			self::addToLoginHistoryEntry($user, 'Failed login attempt');
			throw new PageFlowException('Wrong password');
		}
		
		$userStore->removeBlocking($user);
		self::setLoggedIn($user);
		self::addToLoginHistoryEntry($user, 'Login form');
	}
	
	/**
	 * @throws CriticalError
	 */
	static function setLoggedIn(string $user) {
		Main::sessionStart();
		$_SESSION['user'] = $user;
		$_SESSION['is_loggedIn'] = true;
	}
	
	/**
	 * @throws CriticalError
	 */
	static function setLoggedOut() {
		Main::sessionStart();
		
		$_SESSION['is_loggedIn'] = false;
		$_SESSION['user'] = null;
		
		if(isset($_COOKIE['tokenId']) && isset($_COOKIE['user']))
			Configs::getDataStore()->getLoginTokenStore()->removeLoginToken($_COOKIE['user'], $_COOKIE['tokenId']);
		
		Main::deleteCookie('user');
		Main::deleteCookie('tokenId');
		Main::deleteCookie('token');
	}
	
	/**
	 * @throws CriticalError
	 */
	static function isLoggedIn(): bool {
		Main::sessionStart();
		if(isset($_SESSION['is_loggedIn']) && $_SESSION['is_loggedIn'])
			return true;
		
		if(!isset($_COOKIE['user']) || !isset($_COOKIE['tokenId']) || !isset($_COOKIE['token']))
			return false;
		
		usleep(rand(0, 1000000)); //to prevent Timing Leaks
		
		$user = $_COOKIE['user'];
		$tokenId = $_COOKIE['tokenId'];
		$token = $_COOKIE['token'];
		
		
		$loginTokenStore = Configs::getDataStore()->getLoginTokenStore();
		if(!$loginTokenStore->loginTokenExists($user, $tokenId)) { //can happen when a login was removed; also the tokenId cookie is not critical
			self::setLoggedOut();
			return false;
		}
		else if(self::getHashedToken($token) !== $loginTokenStore->getLoginToken($user, $tokenId)) { //Something fishy is going on
			$ip = $_SERVER['REMOTE_ADDR'];
			$userAgent = $_SERVER['HTTP_USER_AGENT'];
			Main::report("Somebody tried to log in with a broken token. All token for that user have been deleted for security reasons.\nUser: $user,\nIp: $ip,\ntokenId: $tokenId,\ntokenHash: $token,\nUserAgent: $userAgent");
			
			$loginTokenStore->clearAllLoginToken($user);
			self::setLoggedOut();
			return false;
		}
		self::addToLoginHistoryEntry($user, $tokenId);
		
		//Note: Should we renew the token every time? I am not sure.
		// - On the one hand it is good to renew the token to make sure every token can only be used (in the long term) on one device.
		//     This makes it easier to spot if a token has been stolen (because the user gets logged out and there will be another active entry in the token list).
		// - On the other hand, if two token login requests are done at the same time (with the same cookies):
		//     RequestA will change the token_hash which will make RequestB fail and trigger a report because its cookie still has the old token_hash.
		//     Though unlikely, it might happen for example when a browser restores multiple tabs from an old session.
		self::createNewLoginToken($user, $tokenId);
		
		self::setLoggedIn($user);
		
		return true;
	}
	
	static function getUser() {
		return $_SESSION['user'];
	}
	
	static function getCurrentLoginTokenId() {
		return $_COOKIE['tokenId'] ?? null;
	}
	
	static function isAdmin(): bool {
		$permissions = self::getPermissions();
		return isset($permissions['admin']) && $permissions['admin'];
	}
	static function hasPermission(int $studyId, string $permCode): bool {
		$permissions = self::getPermissions();
		return isset($permissions[$permCode]) && in_array($studyId, $permissions[$permCode]);
	}
	static function getPermissions(): array {
		return Configs::getDataStore()->getUserStore()->getPermissions(self::getUser());
	}
	
	private static function addToLoginHistoryEntry(string $user, string $state) {
		Configs::getDataStore()->getUserStore()->addToLoginHistoryEntry($user, [
			time(),
			$state,
			$_SERVER['REMOTE_ADDR'],
			CreateDataSet::stripOneLineInput($_SERVER['HTTP_USER_AGENT'])
		]);
	}
	
	/**
	 * @throws CriticalError
	 */
	static function calcRandomToken(int $length): string {
		//Thanks to: https://www.php.net/manual/en/function.random-bytes.php#118932
		if(function_exists('random_bytes'))
			return bin2hex(random_bytes($length));
		else if(function_exists('mcrypt_create_iv'))
			return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
		else if(function_exists('openssl_random_pseudo_bytes'))
			return bin2hex(openssl_random_pseudo_bytes($length));
		else
			throw new CriticalError('This server does not support random_bytes(), mcrypt_create_iv() or openssl_random_pseudo_bytes()');
	}
	static function getHashedToken(string $tokenHash) {
		//simple hashing is enough. If somebody has access to our data, they dont need the random token anymore
		return hash('sha256', $tokenHash);
	}
	
	/**
	 * @throws CriticalError
	 */
	static function createNewLoginToken(string $user, string $tokenId = null) {
		$loginTokenStore = Configs::getDataStore()->getLoginTokenStore();
		$token = self::calcRandomToken(32);
		
		if($tokenId === null) {
			do {
				$tokenId = Permission::calcRandomToken(16);
			} while ($loginTokenStore->loginTokenExists($user, $tokenId));
		}
		
		$loginTokenStore->saveLoginToken($user, Permission::getHashedToken($token), $tokenId);
		
		//save cookies:
		$expire = time()+31536000; //60*60*24*365
		Main::setCookie('user', $user, $expire);
		Main::setCookie('tokenId', $tokenId, $expire);
		Main::setCookie('token', $token, $expire);
	}
}