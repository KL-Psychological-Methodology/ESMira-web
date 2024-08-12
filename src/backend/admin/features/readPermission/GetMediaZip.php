<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Main;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\Paths;

class GetMediaZip extends HasReadPermission {
	
	function execAndOutput() {
		$pathZip = Paths::fileMediaZip($this->studyId);
		
		Main::setHeader('Cache-Control: no-cache, must-revalidate');
		Main::setHeader('Content-Type: application/octet-stream');
		Main::setHeader('Content-Disposition: attachment; filename=' . Paths::FILENAME_MEDIA_ZIP);
		Main::setHeader('Content-Transfer-Encoding: binary');
		Main::setHeader('Content-Length: ' . filesize($pathZip));
		readfile($pathZip);
	}
	
	function exec(): array {
		throw new CriticalException('Internal error. CreateMediaZip can only be used with execAndOutput()');
	}
}