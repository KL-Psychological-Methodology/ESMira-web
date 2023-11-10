<?php

use backend\Configs;
use backend\fileSystem\PathsFS;

$pathServerStatistics = PathsFS::fileServerStatistics();
if(file_exists($pathServerStatistics)) {
	$handle = fopen($pathServerStatistics, 'r+');
	$statistics = json_decode(fread($handle, filesize($pathServerStatistics)));
	
	$studyStore = Configs::getDataStore()->getStudyStore();
	
	$statistics->total->studies = 0;
	
	foreach($studyStore->getStudyIdList() as $studyId) {
		$study = $studyStore->getStudyConfig($studyId);
		if($study->published ?? false)
			$statistics->total->studies += 1;
	}
	if(fseek($handle, 0) != -1 && ftruncate($handle, 0) && fwrite($handle, json_encode($statistics)))
		fflush($handle);
	fclose($handle);
}
