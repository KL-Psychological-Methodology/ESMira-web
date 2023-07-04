<?php

namespace backend;

use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;

class Permission {
	static function getHashedPass(string $pass) {
		return password_hash($pass,  PASSWORD_DEFAULT);
	}
	
	/**
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
	static function login(string $accountName, string $password) {
		$accountStore = Configs::getDataStore()->getAccountStore();
		$blockTime = $accountStore->getAccountBlockedTime($accountName);
		if($blockTime != 0)
			throw new PageFlowException("Please wait for $blockTime seconds.");
		
		if(!$accountStore->checkAccountLogin($accountName, $password)) {
			usleep(rand(0, 1000000)); //to prevent Timing Leaks
			
			//block next login for a while because of failed attempt:
			$accountStore->createBlocking($accountName);
			self::addToLoginHistoryEntry($accountName, 'Failed login attempt');
			throw new PageFlowException('Wrong password');
		}
		
		$accountStore->removeBlocking($accountName);
		self::setLoggedIn($accountName);
		self::addToLoginHistoryEntry($accountName, 'Login form');
	}
	
	/**
	 * @throws CriticalException
	 */
	static function setLoggedIn(string $user) {
		Main::sessionStart();
		$_SESSION['account'] = $user;
		$_SESSION['is_loggedIn'] = true;
	}
	
	/**
	 * @throws CriticalException
	 */
	static function setLoggedOut() {
		Main::sessionStart();
		
		$_SESSION['is_loggedIn'] = false;
		$_SESSION['account'] = null;
		
		if(isset($_COOKIE['tokenId']) && isset($_COOKIE['account']))
			Configs::getDataStore()->getLoginTokenStore()->removeLoginToken($_COOKIE['account'], $_COOKIE['tokenId']);
		
		Main::deleteCookie('account');
		Main::deleteCookie('tokenId');
		Main::deleteCookie('token');
	}
	
	/**
	 * @throws CriticalException
	 */
	static function isLoggedIn(): bool {
		Main::sessionStart();
		if(isset($_SESSION['is_loggedIn']) && $_SESSION['is_loggedIn'])
			return true;
		
		if(!isset($_COOKIE['account']) || !isset($_COOKIE['tokenId']) || !isset($_COOKIE['token']))
			return false;
		
		usleep(rand(0, 1000000)); //to prevent Timing Leaks
		
		$accountName = $_COOKIE['account'];
		$tokenId = $_COOKIE['tokenId'];
		$token = $_COOKIE['token'];
		
		
		$loginTokenStore = Configs::getDataStore()->getLoginTokenStore();
		if(!$loginTokenStore->loginTokenExists($accountName, $tokenId)) { //can happen when a login was removed; also the tokenId cookie is not critical
			self::setLoggedOut();
			return false;
		}
		else if(self::getHashedToken($token) !== $loginTokenStore->getLoginToken($accountName, $tokenId)) {
			// This either happens when somebody stole the token cookie
			// Or when a browser has cached the session and tries to restore it. This seems to happen mainly on mobile browsers and it seems they
			// just save the request and resend it - which is has an outdated token because the token cookie has changed
			// My first approach would be to log the user out for safety. But since this is happening way more than anticipated and becoming a real annoyance,
			// we will just return false and only force the user to reload the page
//			$ip = $_SERVER['REMOTE_ADDR'];
//			$userAgent = $_SERVER['HTTP_USER_AGENT'];
//			Main::report("Somebody tried to log in with a broken token. All token for that account have been deleted for security reasons.\nAccount: $accountName,\nIp: $ip,\ntokenId: $tokenId,\ntokenHash: $token,\nUserAgent: $userAgent");
//			$loginTokenStore->clearAllLoginToken($accountName);
//			self::setLoggedOut();
			return false;
		}
		self::addToLoginHistoryEntry($accountName, $tokenId);
		
		//Note: Should we renew the token every time? I am not sure.
		// - On the one hand it is good to renew the token to make sure every token can only be used (in the long term) on one device.
		//     This makes it easier to spot if a token has been stolen (because the account gets logged out and there will be another active entry in the token list).
		// - On the other hand, if two token login requests are done at the same time (with the same cookies):
		//     RequestA will change the token_hash which will make RequestB fail and trigger a report because its cookie still has the old token_hash.
		//     Though unlikely, it might happen for example when a browser restores multiple tabs from an old session.
		self::createNewLoginToken($accountName, $tokenId);
		
		self::setLoggedIn($accountName);
		
		return true;
	}
	
	static function getAccountName() {
		return $_SESSION['account'] ?? '';
	}
	
	static function getCurrentLoginTokenId() {
		return $_COOKIE['tokenId'] ?? null;
	}
	
	static function isAdmin(): bool {
		$permissions = self::getPermissions();
		return isset($permissions['admin']) && $permissions['admin'];
	}
	static function canCreate(): bool {
		$permissions = self::getPermissions();
		return isset($permissions['create']) && $permissions['create'];
	}
	static function hasPermission(int $studyId, string $permCode): bool {
		$permissions = self::getPermissions();
		return isset($permissions[$permCode]) && in_array($studyId, $permissions[$permCode]);
	}
	static function getPermissions(): array {
		return Configs::getDataStore()->getAccountStore()->getPermissions(self::getAccountName());
	}
	
	private static function addToLoginHistoryEntry(string $user, string $state) {
		Configs::getDataStore()->getAccountStore()->addToLoginHistoryEntry($user, [
			time(),
			$state,
			$_SERVER['REMOTE_ADDR'],
			CreateDataSet::stripOneLineInput($_SERVER['HTTP_USER_AGENT'])
		]);
	}
	
	/**
	 * @throws CriticalException
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
			throw new CriticalException('This server does not support random_bytes(), mcrypt_create_iv() or openssl_random_pseudo_bytes()');
	}
	static function getHashedToken(string $tokenHash) {
		//simple hashing is enough. If somebody has access to our data, they dont need the random token anymore
		return hash('sha256', $tokenHash);
	}
	
	/**
	 * @throws CriticalException
	 */
	static function createNewLoginToken(string $accountName, string $tokenId = null) {
		$loginTokenStore = Configs::getDataStore()->getLoginTokenStore();
		$token = self::calcRandomToken(32);
		
		if($tokenId === null) {
			do {
				$tokenId = Permission::calcRandomToken(16);
			} while ($loginTokenStore->loginTokenExists($accountName, $tokenId));
		}
		
		$loginTokenStore->saveLoginToken($accountName, Permission::getHashedToken($token), $tokenId);
		
		//save cookies:
		$expire = time()+31536000; //60*60*24*365
		Main::setCookie('account', $accountName, $expire);
		Main::setCookie('tokenId', $tokenId, $expire);
		Main::setCookie('token', $token, $expire);
	}
}