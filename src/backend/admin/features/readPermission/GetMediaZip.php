<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Main;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\Paths;

class GetMediaZip extends HasReadPermission {

	function execAndOutput() {
		@set_time_limit(0);

		$pathZip = Paths::fileMediaZip($this->studyId);
		$chunksize = 5 * (1024 * 1024); // 5 MB chunks
		$size = intval(sprintf("%u", filesize($pathZip)));

		Main::setHeader('Cache-Control: no-cache, must-revalidate');
		Main::setHeader('Content-Type: application/octet-stream');
		Main::setHeader('Content-Disposition: attachment; filename=' . Paths::FILENAME_MEDIA_ZIP);
		Main::setHeader('Content-Transfer-Encoding: binary');
		Main::setHeader('Content-Length: ' . filesize($pathZip));

		if($size > $chunksize) {
			$handle = fopen($pathZip, 'rb');
			while(!feof($handle)) {
				print(@fread($handle, $chunksize));
				ob_flush();
				flush();
			}
			fclose($handle);
		} else
			readfile($pathZip);
	}
	
	function exec(): array {
		throw new CriticalException('Internal error. CreateMediaZip can only be used with execAndOutput()');
	}
}