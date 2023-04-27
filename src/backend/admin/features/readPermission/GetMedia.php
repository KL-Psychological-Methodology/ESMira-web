<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;

class GetMedia extends HasReadPermission {
	public function execAndOutput() {
		if(!isset($_GET['userId']) || !isset($_GET['entryId']) || !isset($_GET['key']) || !isset($_GET['media_type']))
			throw new PageFlowException('Missing data');
		$userId = $_GET['userId'];
		$entryId = (int) $_GET['entryId'];
		$key = $_GET['key'];
		
		switch($_GET['media_type']) {
			case 'image':
				Configs::getDataStore()->getResponsesStore()->outputImageFromResponses($this->studyId, $userId, $entryId, $key);
				break;
			case 'audio':
				Configs::getDataStore()->getResponsesStore()->outputAudioFromResponses($this->studyId, $userId, $entryId, $key);
				break;
			default:
				throw new PageFlowException('Faulty data');
				break;
		}
	}
	
	function exec(): array {
		throw new CriticalException('Internal error. GetMediaImage can only be used with execAndOutput()');
	}
}