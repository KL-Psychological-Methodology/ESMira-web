<?php

namespace backend\admin\features\messagePermission;

use backend\admin\HasMessagePermission;
use backend\Main;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\Permission;

class SendMessage extends HasMessagePermission {
	/**
	 * @throws CriticalException
	 */
	private function sendToAll(string $from, string $content, string $appType = null, string $appVersion = null) {
		$dataStore = Configs::getDataStore();
		$messageStore = $dataStore->getMessagesStore();
		$checkUserdata = $appVersion || $appType;
		
		$participants = Configs::getDataStore()->getStudyStore()->getStudyParticipants($this->studyId);
		foreach($participants as $userId) {
			if($checkUserdata) {
				$userData = $dataStore->getUserDataStore($userId)->getUserData($this->studyId);
				
				if(($appVersion && $userData->appVersion != $appVersion) || ($appType &&$userData->appType != $appType))
					continue;
			}
			$messageStore->sendMessage($this->studyId, $userId, $from, $content);
		}
	}
	
	function exec(): array {
		$json = json_decode(Main::getRawPostInput());
		
		if(!$json)
			throw new PageFlowException('Faulty data');
		else if(!isset($json->content) || !isset($json->userId))
			throw new PageFlowException('Missing data');
		
		$from = Permission::getAccountName();
		$content = $json->content;
		$toAll = $json->toAll;
		
		if(strlen($content) < 2)
			throw new PageFlowException("Message is too short");
		
		if($toAll) {
			$appVersion = $json->appVersion ?? null;
			$appType = $json->appType ?? null;
			$this->sendToAll($from, $content, $appType, $appVersion);
			return [];
		}
		else {
			$userId = $json->userId;
			if(!Main::strictCheckInput($userId))
				throw new PageFlowException('Recipient is faulty');
			
			Configs::getDataStore()->getMessagesStore()->sendMessage($this->studyId, $userId, $from, $content);
			
			$c = new MessageSetRead();
			return $c->exec();
		}
	}
}