<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Main;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\Paths;

class CreateMediaZip extends HasReadPermission {
	
	function execAndOutput() {
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache');
		
		echo "Start\n\n";

		if (ob_get_contents())
			ob_end_flush();
		flush();

		$pathZip = Paths::fileMediaZip($this->studyId);
		if(!file_exists($pathZip)) //zip was not created or deleted, so we create it:
			Configs::getDataStore()->getResponsesStore()->createMediaZip($this->studyId);

		echo "event: finished\n";
		echo "data: \n\n";

		if (ob_get_contents())
			ob_end_flush();
		flush();
	}
	
	function exec(): array {
		throw new CriticalException('Internal error. CreateMediaZip can only be used with execAndOutput()');
	}
}